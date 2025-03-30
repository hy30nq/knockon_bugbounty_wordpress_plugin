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
    만약 파싱 오류(예: exit status 255)가 발생하면 None을 반환합니다.
    """
    try:
        # PHP 파일이 존재하는지 확인
        if not os.path.exists(file_path) or not os.path.isfile(file_path):
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: File does not exist{ConsoleColor.ENDC}")
            return None
            
        # 파일 크기 체크 (너무 큰 파일은 건너뜀)
        file_size = os.path.getsize(file_path)
        if file_size > 1024 * 1024:  # 1MB 이상인 파일은 건너뜀
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: File too large ({file_size/1024:.1f}KB){ConsoleColor.ENDC}")
            return None
            
        result = subprocess.run(["php", "php_parser.php", file_path],
                                capture_output=True, text=True)
        if result.returncode != 0:
            # 오류 메시지를 출력하고 해당 파일은 건너뜁니다.
            print(f"{ConsoleColor.FAIL}Error parsing {file_path}: {result.stderr.strip()}{ConsoleColor.ENDC}")
            return None
            
        if not result.stdout.strip():
            # 출력이 비어있으면 None 반환
            print(f"{ConsoleColor.WARNING}Skip parsing {file_path}: Empty output{ConsoleColor.ENDC}")
            return None
            
        try:
            ast_json = json.loads(result.stdout)
            # 빈 배열이면 None 반환
            if not ast_json:
                return None
            return ast_json
        except json.JSONDecodeError as je:
            print(f"{ConsoleColor.FAIL}JSON decode error for {file_path}: {je}{ConsoleColor.ENDC}")
            return None
    except Exception as e:
        print(f"{ConsoleColor.FAIL}Error parsing {file_path}: {e}{ConsoleColor.ENDC}")
        return None

def check_vulnerability(expr):
    """
    AST 노드(expr)를 검사하여, echo 구문 내에서 슈퍼글로벌 변수($_GET, $_POST, $_REQUEST 등)를 직접 사용하는 경우를 취약점으로 간주합니다.
    """
    if not isinstance(expr, dict):
        return None
    node_type = expr.get("nodeType", "")
    if node_type == "Expr_Variable":
        var_name = expr.get("name", "")
        if var_name in ["_GET", "_POST", "_REQUEST"]:
            return f"Direct use of ${var_name} detected."
    return None

def analyze_ast(ast):
    """
    AST를 재귀적으로 순회하며, echo 구문(Stmt_Echo) 내 취약점이 있는지 탐지합니다.
    발견 시, 해당 노드의 라인 번호와 간략한 취약점 설명을 vulnerabilities 리스트에 추가합니다.
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
    주어진 PHP 파일을 파싱하여 AST를 얻은 후, AST 분석을 통해 XSS 취약점 정보를 리스트로 반환합니다.
    파싱에 실패한 경우 빈 리스트를 반환합니다.
    """
    ast = parse_php_file(file_path)
    if not ast:
        return []
    vulns = analyze_ast(ast)
    return vulns

def scan_plugin(plugin_dir):
    """
    플러그인 폴더 내의 모든 PHP 파일을 대상으로 PHP AST 기반 분석을 수행합니다.
    파일별 취약점 정보를 딕셔너리 형태로 수집하여 반환합니다.
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
