import os
import re
import json
import shutil
import zipfile
import subprocess
import time
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed

# 콘솔 출력 색상 (선택 사항)
class ConsoleColor:
    OKGREEN = "\033[92m"
    WARNING = "\033[93m"
    FAIL = "\033[91m"
    OKBLUE = "\033[94m"
    ENDC = "\033[0m"

#########################################
# 1. ZIP 압축 해제 및 PHP 파일 검색 함수
#########################################

def extract_zip_files(plugins_dir):
    for file in os.listdir(plugins_dir):
        if file.lower().endswith('.zip'):
            zip_path = os.path.join(plugins_dir, file)
            extract_dir = os.path.join(plugins_dir, file[:-4])
            try:
                with zipfile.ZipFile(zip_path, 'r') as zip_ref:
                    zip_ref.extractall(extract_dir)
                print(f"{ConsoleColor.OKGREEN}[+] {file} 압축 해제 완료 -> {extract_dir}{ConsoleColor.ENDC}")
            except Exception as e:
                print(f"{ConsoleColor.FAIL}[!] {file} 압축 해제 실패: {e}{ConsoleColor.ENDC}")
                continue
            os.remove(zip_path)
            print(f"{ConsoleColor.WARNING}[+] {file} 삭제 완료{ConsoleColor.ENDC}")
            time.sleep(0.1)

def find_php_files(directory):
    return list(Path(directory).rglob("*.php"))

#########################################
# 2. PHP AST 파서 호출 및 분석 함수
#########################################

def parse_php_file(file_path):
    """
    php_parser.php 스크립트를 호출하여 주어진 PHP 파일의 AST를 JSON으로 반환합니다.
    만약 파싱 오류가 발생하면 None을 반환합니다.
    """
    try:
        if not os.path.exists(file_path) or not os.path.isfile(file_path):
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: File does not exist{ConsoleColor.ENDC}")
            return None
            
        file_size = os.path.getsize(file_path)
        if file_size > 1024 * 1024:  # 1MB 이상은 건너뜀
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: File too large ({file_size/1024:.1f}KB){ConsoleColor.ENDC}")
            return None
            
        result = subprocess.run(["php", "php_parser.php", file_path],
                                capture_output=True, text=True)
        if result.returncode != 0:
            print(f"{ConsoleColor.FAIL}Error parsing {file_path}: {result.stderr.strip()}{ConsoleColor.ENDC}")
            return None
        if not result.stdout.strip():
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: Empty output{ConsoleColor.ENDC}")
            return None
        try:
            ast_json = json.loads(result.stdout)
            if not ast_json:
                return None
            return ast_json
        except json.JSONDecodeError as je:
            print(f"{ConsoleColor.FAIL}JSON decode error for {file_path}: {je}{ConsoleColor.ENDC}")
            return None
    except Exception as e:
        print(f"{ConsoleColor.FAIL}Error parsing {file_path}: {e}{ConsoleColor.ENDC}")
        return None

# 안전 함수 목록
SAFE_FUNCTIONS = {"esc_html", "esc_attr", "wp_kses", "esc_url", "esc_js"}

def is_unsafe_expr(expr):
    """
    재귀적으로 AST 노드(expr)를 검사하여, 안전하게 이스케이프되지 않은 슈퍼글로벌 변수의 사용이 있는지 확인합니다.
    - Expr_Variable: $_GET, $_POST, $_REQUEST가 직접 사용되면 unsafe.
    - Expr_FuncCall: 만약 호출된 함수가 SAFE_FUNCTIONS에 포함되어 있으면 안전, 그렇지 않으면 인자들을 재귀적으로 검사.
    - 기타 하위 노드들을 순회합니다.
    """
    if not isinstance(expr, dict):
        return False
    node_type = expr.get("nodeType", "")
    if node_type == "Expr_Variable":
        var_name = expr.get("name", "")
        if var_name in ["_GET", "_POST", "_REQUEST"]:
            return True
    if node_type == "Expr_FuncCall":
        func_name = None
        name_node = expr.get("name", {})
        if isinstance(name_node, dict) and "parts" in name_node:
            parts = name_node["parts"]
            if isinstance(parts, list) and parts:
                func_name = parts[-1]
        if func_name and func_name in SAFE_FUNCTIONS:
            return False  # 안전 함수 호출이면 safe
        # 안전 함수가 아니라면 인자들을 체크
        for arg in expr.get("args", []):
            if is_unsafe_expr(arg):
                return True
    # 재귀적으로 모든 하위 항목 검사
    for key, value in expr.items():
        if isinstance(value, dict):
            if is_unsafe_expr(value):
                return True
        elif isinstance(value, list):
            for item in value:
                if isinstance(item, dict) and is_unsafe_expr(item):
                    return True
    return False

def check_vulnerability(expr):
    """
    AST 노드(expr)를 검사하여 unsafe한 사용이 있는지 확인합니다.
    """
    if is_unsafe_expr(expr):
        return "Unsafe output detected without proper escaping."
    return None

def analyze_ast(ast):
    """
    AST를 재귀적으로 순회하며, echo 구문(Stmt_Echo) 내에서 취약점이 있는지 탐지합니다.
    발견 시 해당 노드의 라인 번호와 취약점 설명을 vulnerabilities 리스트에 추가합니다.
    """
    vulnerabilities = []

    def traverse(node):
        if isinstance(node, dict):
            node_type = node.get("nodeType", "")
            if node_type == "Stmt_Echo":
                exprs = node.get("exprs", [])
                for expr in exprs:
                    vuln = check_vulnerability(expr)
                    if vuln:
                        vulnerabilities.append({
                            "line": node.get("line", "unknown"),
                            "snippet": vuln
                        })
            for key, value in node.items():
                if isinstance(value, (dict, list)):
                    traverse(value)
        elif isinstance(node, list):
            for item in node:
                traverse(item)

    traverse(ast)
    return vulnerabilities

def scan_php_file_for_xss_ast(file_path):
    """
    주어진 PHP 파일을 파싱한 후, AST 분석을 통해 XSS 취약점 정보를 리스트로 반환합니다.
    파싱 실패 시 빈 리스트 반환.
    """
    ast = parse_php_file(file_path)
    if not ast:
        return []
    vulns = analyze_ast(ast)
    return vulns

def scan_plugin(plugin_dir):
    """
    플러그인 폴더 내의 모든 PHP 파일을 대상으로 AST 기반 분석을 수행하여,
    파일별 취약점 정보를 딕셔너리로 수집하여 반환합니다.
    """
    results = {}
    php_files = find_php_files(plugin_dir)
    with ThreadPoolExecutor(max_workers=10) as executor:
        future_to_file = {executor.submit(scan_php_file_for_xss_ast, str(php_file)): str(php_file) for php_file in php_files}
        for future in as_completed(future_to_file):
            file_path = future_to_file[future]
            try:
                vulns = future.result()
                if vulns:
                    results[file_path] = vulns
            except Exception as e:
                print(f"Error scanning {file_path}: {e}")
    return results

#########################################
# 3. 플러그인 처리 및 JSON 보고서 생성
#########################################

def process_plugins(plugins_dir, success_dir, report_file):
    extract_zip_files(plugins_dir)
    report = {}
    for item in os.listdir(plugins_dir):
        item_path = os.path.join(plugins_dir, item)
        if os.path.isdir(item_path):
            print(f"\n[*] 플러그인 '{item}' 스캔 시작...")
            vulns = scan_plugin(item_path)
            if vulns:
                print(f"{ConsoleColor.OKGREEN}[+] XSS 취약점 발견: '{item}'{ConsoleColor.ENDC}")
                report[item] = vulns
                dest_path = os.path.join(success_dir, item)
                if os.path.exists(dest_path):
                    shutil.rmtree(dest_path)
                shutil.move(item_path, success_dir)
                print(f"{ConsoleColor.OKGREEN}[+] '{item}' 폴더가 success 폴더로 이동됨.{ConsoleColor.ENDC}")
            else:
                print(f"{ConsoleColor.FAIL}[-] '{item}'에서 XSS 취약점 미발견 → 폴더 삭제됨{ConsoleColor.ENDC}")
                shutil.rmtree(item_path)
            time.sleep(0.1)
    with open(report_file, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=4, ensure_ascii=False)
    print(f"{ConsoleColor.OKBLUE}Report generated: {report_file}{ConsoleColor.ENDC}")

def main():
    plugins_dir = "./plugins"
    success_dir = "./success"
    report_file = "vulnerability_report.json"
    if not os.path.exists(success_dir):
        os.makedirs(success_dir)
    process_plugins(plugins_dir, success_dir, report_file)

if __name__ == "__main__":
    main()
