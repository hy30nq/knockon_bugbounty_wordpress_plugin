import os
import zipfile
import json
import re
import shutil
import time
import torch
from transformers import AutoTokenizer, AutoModelForSequenceClassification
from collections import defaultdict
from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn, BarColumn, TimeElapsedColumn
from rich.panel import Panel
from rich.syntax import Syntax
from rich.markdown import Markdown

# Rich 콘솔 객체 생성
console = Console()

# 결과를 저장할 JSON 파일 경로
RESULT_JSON_FILE = 'wordpress_xss_vulnerabilities.json'

# AI 모델 초기화
def initialize_ai_model():
    try:
        # 작은 크기의 모델 사용 (예: DistilBERT)
        model_name = "distilbert-base-uncased"
        tokenizer = AutoTokenizer.from_pretrained(model_name)
        model = AutoModelForSequenceClassification.from_pretrained(model_name, num_labels=2)  # 0: 안전, 1: 취약
        return tokenizer, model
    except Exception as e:
        console.print(f"[yellow]AI 모델 초기화 실패: {str(e)}[/yellow]")
        return None, None

def analyze_code_with_ai(code_snippet, tokenizer, model):
    try:
        # 코드를 토큰화
        inputs = tokenizer(code_snippet, return_tensors="pt", truncation=True, max_length=512)
        
        # 추론
        with torch.no_grad():
            outputs = model(**inputs)
            predictions = torch.softmax(outputs.logits, dim=1)
            vulnerability_score = predictions[0][1].item()
        
        return vulnerability_score > 0.5  # 0.5 이상이면 취약점으로 판단
    except Exception as e:
        console.print(f"[yellow]AI 분석 중 오류 발생: {str(e)}[/yellow]")
        return False

def find_input_sources(content):
    # 사용자 입력을 받는 소스 패턴 - 더 엄격한 패턴으로 수정
    input_patterns = {
        'GET': r'\$_GET\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'POST': r'\$_POST\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'REQUEST': r'\$_REQUEST\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'COOKIE': r'\$_COOKIE\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'FILES': r'\$_FILES\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'SERVER': r'\$_SERVER\s*\[\s*[\'"]HTTP_([^\'"]+)[\'"]\s*\]',  # HTTP 헤더만 추적
        'wp_query': r'get_query_var\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)'  # WordPress 쿼리 변수
    }
    
    inputs = defaultdict(list)
    for source, pattern in input_patterns.items():
        matches = re.finditer(pattern, content)
        for match in matches:
            param_name = match.group(1)
            inputs[source].append(param_name)
    
    return inputs

def find_variable_assignments(content):
    # 변수 할당 패턴 찾기 (PHP 변수 할당 규칙에 맞게 개선)
    var_assignments = {}
    
    # 기본 변수 할당
    assignment_pattern = r'\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*(.*?);'
    
    for match in re.finditer(assignment_pattern, content):
        var_name = match.group(1)
        var_value = match.group(2).strip()
        var_assignments[var_name] = var_value
    
    return var_assignments

def find_sinks(content, file_path):
    # XSS 취약점 발생 가능한 싱크 패턴 - 더 엄격한 패턴으로 수정
    sink_patterns = [
        # PHP 출력 함수
        (r'echo\s+((?:[^;]|(?:\\\;))+);', 'echo'),
        (r'print\s+((?:[^;]|(?:\\\;))+);', 'print'),
        (r'<\?=\s*((?:[^\?]|(?:\\\?))+)\?>', '<?='),
        (r'printf\s*\(\s*([^,\)]+)(?:,|\))', 'printf'),
        
        # HTML 속성 및 콘텐츠
        (r'\.innerHTML\s*=\s*(.*?);', 'innerHTML'),
        (r'\.outerHTML\s*=\s*(.*?);', 'outerHTML'),
        (r'\.insertAdjacentHTML\s*\(\s*[\'"][^\'"]*[\'"]\s*,\s*(.*?)\)', 'insertAdjacentHTML'),
        
        # JavaScript 함수
        (r'document\.write\s*\((.*?)\)', 'document.write'),
        (r'\.html\s*\(\s*(.*?)\s*\)', 'jQuery.html()'),
        (r'\.append\s*\(\s*(.*?)\s*\)', 'jQuery.append()'),
        
        # WordPress 특정 함수
        (r'wp_die\s*\(\s*(.*?)[,\)]', 'wp_die'),
        (r'_e\s*\(\s*(.*?)[,\)]', '_e'),
        (r'__\s*\(\s*(.*?)[,\)]', '__')
    ]
    
    sinks = []
    for pattern, sink_type in sink_patterns:
        matches = re.finditer(pattern, content)
        for match in matches:
            sink_content = match.group(1)
            line_number = content[:match.start()].count('\n') + 1
            context_start = max(0, match.start() - 50)
            context_end = min(len(content), match.end() + 50)
            context = content[context_start:context_end].strip()
            
            sinks.append({
                'type': sink_type,
                'content': sink_content.strip(),
                'line': line_number,
                'file': file_path,
                'context': context
            })
    
    return sinks

def is_sanitized(code):
    # 보안 함수 사용 여부 확인 (더 정확한 패턴)
    sanitization_functions = [
        # PHP 기본 이스케이프 함수
        r'htmlspecialchars\s*\(',
        r'htmlentities\s*\(',
        r'strip_tags\s*\(',
        r'addslashes\s*\(',
        r'stripslashes\s*\(',
        r'intval\s*\(',
        r'floatval\s*\(',
        r'abs\s*\(',
        r'filter_var\s*\([^,]+,\s*FILTER_SANITIZE_',
        
        # WordPress 이스케이프 함수
        r'esc_html\s*\(',
        r'esc_attr\s*\(',
        r'esc_url\s*\(',
        r'esc_js\s*\(',
        r'esc_textarea\s*\(',
        r'sanitize_text_field\s*\(',
        r'sanitize_title\s*\(',
        r'sanitize_email\s*\(',
        r'wp_kses\s*\(',
        r'wp_kses_post\s*\(',
        r'absint\s*\('
    ]
    
    for func_pattern in sanitization_functions:
        if re.search(func_pattern, code):
            return True
    return False

def check_dom_xss(content):
    # DOM XSS 취약점 패턴 검사
    dom_xss_patterns = [
        r'document\.URL',
        r'document\.documentURI',
        r'document\.location',
        r'document\.referrer',
        r'window\.name',
        r'location\.hash',
        r'location\.search',
        r'location\.href'
    ]
    
    dom_xss_sinks = [
        r'\.innerHTML',
        r'\.outerHTML',
        r'\.insertAdjacentHTML',
        r'document\.write',
        r'document\.writeln',
        r'eval\(',
        r'setTimeout\(',
        r'setInterval\(',
        r'new\s+Function\('
    ]
    
    for source in dom_xss_patterns:
        for sink in dom_xss_sinks:
            # 소스와 싱크 사이의 코드 검사
            pattern = f"{source}[\\s\\S]*?{sink}"
            if re.search(pattern, content):
                return True
    
    return False

def trace_data_flow(content, inputs, sinks, var_assignments):
    vulnerabilities = []
    
    # 변수 추적을 위한 그래프 구축
    var_graph = {}
    for var_name, var_value in var_assignments.items():
        var_graph[var_name] = var_value
    
    # 입력 소스에서 변수로의 매핑
    input_vars = {}
    for source, params in inputs.items():
        for param in params:
            source_pattern = fr'\$_{source}\s*\[\s*[\'"]?{param}[\'"]?\s*\]'
            if source == 'wp_query':
                source_pattern = fr'get_query_var\s*\(\s*[\'"]?{param}[\'"]?\s*\)'
                
            for var_name, var_value in var_assignments.items():
                if re.search(source_pattern, var_value):
                    input_vars[var_name] = (source, param)
    
    # 변수 간 의존성 추적 (최대 3단계)
    for _ in range(3):
        for var_name, var_value in list(var_graph.items()):
            for other_var in var_graph:
                other_pattern = fr'\${other_var}\b'
                if re.search(other_pattern, var_value):
                    if other_var in input_vars and var_name not in input_vars:
                        input_vars[var_name] = input_vars[other_var]
    
    for sink in sinks:
        sink_content = sink['content']
        is_vulnerable = False
        vulnerable_source = None
        sanitized = is_sanitized(sink_content)
        
        # 직접 입력 소스 사용 확인
        for source, params in inputs.items():
            for param in params:
                if source == 'wp_query':
                    source_pattern = fr'get_query_var\s*\(\s*[\'"]?{param}[\'"]?\s*\)'
                else:
                    source_pattern = fr'\$_{source}\s*\[\s*[\'"]?{param}[\'"]?\s*\]'
                
                if re.search(source_pattern, sink_content):
                    if not sanitized:
                        is_vulnerable = True
                        vulnerable_source = f'{source}["{param}"]'
        
        # 변수를 통한 간접 입력 소스 사용 확인
        for var_name in var_graph:
            var_pattern = fr'\${var_name}\b'
            if re.search(var_pattern, sink_content):
                # 변수가 입력 소스에서 왔는지 확인
                if var_name in input_vars and not sanitized:
                    source, param = input_vars[var_name]
                    is_vulnerable = True
                    vulnerable_source = f'{source}["{param}"] -> ${var_name}'
        
        if is_vulnerable:
            vulnerabilities.append({
                'source': vulnerable_source,
                'sink': sink,
                'description': f'사용자 입력 {vulnerable_source}이(가) {sink["type"]}에서 필터링 없이 출력됨',
                'severity': 'High' if 'innerHTML' in sink['type'] or 'document.write' in sink['type'] else 'Medium'
            })
    
    # DOM XSS 취약점 검사
    if check_dom_xss(content):
        vulnerabilities.append({
            'source': 'DOM Source (location, document.URL, etc.)',
            'sink': {
                'type': 'DOM Sink',
                'content': 'DOM Manipulation',
                'file': sinks[0]['file'] if sinks else 'Unknown',
                'line': 0
            },
            'description': 'DOM 기반 XSS 취약점 발견됨',
            'severity': 'High'
        })
    
    return vulnerabilities

def find_vulnerable_patterns(content):
    # 기존 패턴 기반 검사
    patterns = [
        r'echo\s+\$_(?:GET|POST|REQUEST|COOKIE)',
        r'print\s+\$_(?:GET|POST|REQUEST|COOKIE)',
        r'<\?=\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'document\.write\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'\.innerHTML\s*=\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'\.outerHTML\s*=\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'\.insertAdjacentHTML\s*\(\s*[\'"][^\'"]*[\'"]\s*,\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'\.html\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'\.append\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'wp_die\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'_e\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)',
        r'__\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)'
    ]
    
    vulnerabilities = []
    for pattern in patterns:
        matches = re.finditer(pattern, content)
        for match in matches:
            line_number = content[:match.start()].count('\n') + 1
            context_start = max(0, match.start() - 50)
            context_end = min(len(content), match.end() + 50)
            context = content[context_start:context_end].strip()
            
            vulnerabilities.append({
                'pattern': pattern,
                'line': line_number,
                'context': context
            })
    
    return vulnerabilities

def analyze_php_file(file_path, relative_path, tokenizer=None, model=None):
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # 입력 소스 찾기
        inputs = find_input_sources(content)
        
        # 변수 할당 찾기
        var_assignments = find_variable_assignments(content)
        
        # 싱크 찾기
        sinks = find_sinks(content, relative_path)
        
        # 패턴 기반 취약점 검사
        pattern_vulnerabilities = find_vulnerable_patterns(content)
        
        # AI 기반 취약점 검사
        ai_vulnerabilities = []
        if tokenizer and model:
            # 코드를 512 토큰 단위로 분할하여 분석
            code_chunks = [content[i:i+512] for i in range(0, len(content), 512)]
            for chunk in code_chunks:
                if analyze_code_with_ai(chunk, tokenizer, model):
                    ai_vulnerabilities.append({
                        'type': 'AI Detected',
                        'content': chunk[:100] + '...',  # 첫 100자만 표시
                        'line': content[:content.find(chunk)].count('\n') + 1
                    })
        
        # 데이터 흐름 추적
        vulnerabilities = trace_data_flow(content, inputs, sinks, var_assignments)
        
        # 모든 취약점 통합
        all_vulnerabilities = vulnerabilities + pattern_vulnerabilities + ai_vulnerabilities
        
        return inputs, sinks, all_vulnerabilities
    except Exception as e:
        console.print(f"[bold red]파일 분석 중 오류 발생: {file_path} - {str(e)}[/bold red]")
        return {}, [], []

def scan_plugin(plugin_path, progress=None, task_id=None, tokenizer=None, model=None):
    all_vulnerabilities = []
    all_inputs = defaultdict(list)
    all_sinks = []
    vulnerable_files = []
    
    plugin_name = os.path.basename(plugin_path)[:-4]
    
    if progress and task_id is not None:
        progress.update(task_id, description=f"[cyan]분석 중: {plugin_name}[/cyan]")
    
    with zipfile.ZipFile(plugin_path, 'r') as zip_ref:
        temp_dir = os.path.join('temp', plugin_name)
        zip_ref.extractall(temp_dir)
        
        php_files = []
        for root, _, files in os.walk(temp_dir):
            for file in files:
                if file.endswith('.php'):
                    php_files.append((os.path.join(root, file), os.path.relpath(os.path.join(root, file), temp_dir)))
        
        if progress and task_id is not None:
            progress.update(task_id, total=len(php_files) + 1)
            progress.update(task_id, advance=1)
        
        for file_path, relative_path in php_files:
            if progress and task_id is not None:
                progress.update(task_id, description=f"[cyan]분석 중: {plugin_name} - {relative_path}[/cyan]")
            
            inputs, sinks, vulnerabilities = analyze_php_file(file_path, relative_path, tokenizer, model)
            
            for source, params in inputs.items():
                for param in params:
                    if param not in all_inputs[source]:
                        all_inputs[source].append(param)
            
            all_sinks.extend(sinks)
            
            if vulnerabilities:
                vulnerable_files.append(relative_path)
                all_vulnerabilities.extend(vulnerabilities)
            
            if progress and task_id is not None:
                progress.update(task_id, advance=1)
    
    # 임시 디렉토리 정리
    shutil.rmtree(temp_dir)
    
    if all_vulnerabilities:
        # success 폴더로 플러그인 이동
        success_dir = './success'
        if not os.path.exists(success_dir):
            os.makedirs(success_dir)
        
        # 플러그인 파일 복사
        shutil.copy2(plugin_path, os.path.join(success_dir, os.path.basename(plugin_path)))
        
        # 결과 생성
        result = {
            'plugin': plugin_name,
            'inputs': {source: params for source, params in all_inputs.items()},
            'vulnerabilities': all_vulnerabilities,
            'vulnerable_files': vulnerable_files,
            'total_vulnerabilities': len(all_vulnerabilities)
        }
        
        # 결과 JSON에 추가
        append_to_results(result)
        
        return True, result
    else:
        # 취약점이 없으면 원본 ZIP 파일 삭제
        os.remove(plugin_path)
        return False, None

def append_to_results(result):
    # 기존 결과 파일 읽기
    if os.path.exists(RESULT_JSON_FILE):
        try:
            with open(RESULT_JSON_FILE, 'r', encoding='utf-8') as f:
                data = json.load(f)
                # 데이터가 리스트가 아닌 경우 리스트로 변환
                if isinstance(data, dict):
                    all_results = [data]
                elif isinstance(data, list):
                    all_results = data
                else:
                    all_results = []
        except (json.JSONDecodeError, FileNotFoundError):
            all_results = []
    else:
        all_results = []
    
    # 새 결과 추가
    all_results.append(result)
    
    # 파일에 저장
    with open(RESULT_JSON_FILE, 'w', encoding='utf-8') as f:
        json.dump(all_results, f, indent=2, ensure_ascii=False)

def display_vulnerability_details(result):
    console.print(Panel(f"[bold yellow]플러그인: {result['plugin']}[/bold yellow]", expand=False))
    
    # 취약한 파일 목록 표시
    console.print("[bold cyan]취약한 파일:[/bold cyan]")
    for file in result['vulnerable_files']:
        console.print(f"  [yellow]• {file}[/yellow]")
    
    # 취약점 테이블 생성
    table = Table(title=f"발견된 XSS 취약점 ({result['total_vulnerabilities']}개)")
    table.add_column("파일", style="cyan")
    table.add_column("라인", style="blue")
    table.add_column("유형", style="magenta")
    table.add_column("소스", style="green")
    table.add_column("심각도", style="yellow")
    
    for vuln in result['vulnerabilities']:
        table.add_row(
            vuln['sink']['file'],
            str(vuln['sink']['line']),
            vuln['sink']['type'],
            vuln['source'],
            vuln['severity']
        )
    
    console.print(table)
    
    # 코드 컨텍스트 표시
    console.print("[bold cyan]취약한 코드 샘플:[/bold cyan]")
    for i, vuln in enumerate(result['vulnerabilities'][:3]):  # 처음 3개만 표시
        sink = vuln['sink']
        if 'context' in sink:
            console.print(f"[bold yellow]취약점 #{i+1} ({sink['file']}:{sink['line']})[/bold yellow]")
            syntax = Syntax(sink['context'], "php", theme="monokai", line_numbers=True)
            console.print(syntax)
    
    console.print("\n")

def main():
    plugins_folder = './plugins'
    success_folder = './success'
    
    # 필요한 디렉토리 생성
    for folder in [plugins_folder, success_folder, 'temp']:
        if not os.path.exists(folder):
            os.makedirs(folder)

    # AI 모델 초기화
    console.print("[cyan]AI 모델을 초기화하는 중...[/cyan]")
    tokenizer, model = initialize_ai_model()
    
    if tokenizer and model:
        console.print("[green]AI 모델 초기화 완료![/green]")
    else:
        console.print("[yellow]AI 모델을 사용하지 않고 패턴 기반 분석만 수행합니다.[/yellow]")

    # 플러그인 목록 가져오기
    plugin_files = [f for f in os.listdir(plugins_folder) if f.endswith('.zip')]

    if not plugin_files:
        console.print("[bold red]분석할 플러그인이 없습니다. './plugins' 폴더에 WordPress 플러그인 ZIP 파일을 넣어주세요.[/bold red]")
        return

    console.print(Panel(f"[bold green]WordPress 플러그인 XSS 취약점 탐지기[/bold green]", subtitle="v1.0"))
    console.print(f"[cyan]총 {len(plugin_files)}개의 플러그인을 분석합니다...[/cyan]\n")

    vulnerable_plugins = []

    with Progress(
        SpinnerColumn(),
        TextColumn("[progress.description]{task.description}"),
        BarColumn(),
        TextColumn("[progress.percentage]{task.percentage:>3.0f}%"),
        TimeElapsedColumn()
    ) as progress:
        overall_task = progress.add_task("[yellow]전체 진행 상황", total=len(plugin_files))
    
        for plugin_file in plugin_files:
            plugin_path = os.path.join(plugins_folder, plugin_file)
            task_id = progress.add_task(f"[cyan]분석 중: {plugin_file}[/cyan]", total=100)
        
            has_vulnerabilities, result = scan_plugin(plugin_path, progress, task_id, tokenizer, model)
        
            if has_vulnerabilities:
                vulnerable_plugins.append(result)
        
            progress.update(overall_task, advance=1)
            progress.remove_task(task_id)

    # 결과 요약 출력
    console.print("\n[bold green]분석 완료![/bold green]\n")

    if vulnerable_plugins:
        console.print(Panel(f"[bold red]취약점이 발견된 플러그인: {len(vulnerable_plugins)}개[/bold red]", expand=False))
        
        # 취약점 요약 테이블
        summary_table = Table(title="취약점 요약")
        summary_table.add_column("플러그인", style="cyan")
        summary_table.add_column("취약점 수", style="red")
        summary_table.add_column("취약한 파일 수", style="yellow")
        
        for result in vulnerable_plugins:
            summary_table.add_row(
                result['plugin'],
                str(result['total_vulnerabilities']),
                str(len(result['vulnerable_files']))
            )
        
        console.print(summary_table)
        console.print(f"\n[green]취약점이 발견된 플러그인은 './success' 폴더에 저장되었습니다.[/green]")
        console.print(f"[green]상세 취약점 정보는 '{RESULT_JSON_FILE}' 파일에 저장되었습니다.[/green]\n")
        
        # 각 플러그인의 취약점 상세 정보 표시
        console.print("[bold yellow]취약점 상세 정보를 표시하시겠습니까? (y/n)[/bold yellow]", end=" ")
        choice = input().strip().lower()
        
        if choice == 'y':
            for result in vulnerable_plugins:
                display_vulnerability_details(result)
    else:
        console.print("[green]취약점이 발견된 플러그인이 없습니다.[/green]")

    # 임시 디렉토리 정리
    if os.path.exists('temp'):
        shutil.rmtree('temp')

if __name__ == "__main__":
    main()
