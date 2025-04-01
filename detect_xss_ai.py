import os
import zipfile
import json
import re
import shutil
import time
import torch

from collections import defaultdict
from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn, BarColumn, TimeElapsedColumn
from rich.panel import Panel
from rich.syntax import Syntax
from rich.markdown import Markdown

# huggingface/transformers & datasets
from transformers import AutoTokenizer, AutoModelForSequenceClassification, Trainer, TrainingArguments
from datasets import load_dataset

# ----------------------------------------
# 설정
# ----------------------------------------
console = Console()

# 결과를 저장할 JSON 파일 경로
RESULT_JSON_FILE = 'wordpress_xss_vulnerabilities.json'

# 파인 튜닝된 모델 저장 경로 (train_codebert_xss()가 완료된 뒤 생성됨)
FINETUNED_MODEL_DIR = "./fine_tuned_codebert"

# ----------------------------------------
# 1) 파인 튜닝 함수 (원하실 때 호출)
# ----------------------------------------
def train_codebert_xss(
    dataset_path: str = "xss_dataset.json",
    output_dir: str = FINETUNED_MODEL_DIR,
    num_train_epochs: int = 3,
    batch_size: int = 8,
    learning_rate: float = 2e-5,
):
    """
    CodeBERT 모델을 XSS 취약/안전 코드 이진 분류 태스크로 파인 튜닝.
    dataset_path: json 파일 경로 (형식: [{"code": "...", "label": 0 or 1}, ...])
    output_dir: 파인 튜닝된 모델 저장 디렉토리
    """
    console.print("[cyan]XSS 학습용 데이터셋을 로드 중...[/cyan]")
    # JSON 형식 데이터셋 로드 (datasets 라이브러리)
    dataset = load_dataset("json", data_files=dataset_path)

    # train/eval 나눌 수 있으면 나누고, 여기서는 전부 train으로만 가정
    if "train" not in dataset:
        dataset = dataset["train"].train_test_split(test_size=0.2)
    else:
        dataset = dataset

    console.print("[green]데이터셋 로드 완료![/green]")

    # CodeBERT 불러오기
    model_name = "microsoft/codebert-base"
    console.print("[cyan]CodeBERT 로딩...[/cyan]")
    tokenizer = AutoTokenizer.from_pretrained(model_name)
    model = AutoModelForSequenceClassification.from_pretrained(model_name, num_labels=2)

    # 전처리 함수
    def tokenize_fn(example):
        return tokenizer(example["code"], truncation=True, padding="max_length", max_length=512)

    # map을 이용해 데이터셋 전체 토큰화
    train_dataset = dataset["train"].map(tokenize_fn, batched=True)
    eval_dataset = dataset["test"].map(tokenize_fn, batched=True)

    # 텐서 형식 지정
    train_dataset.set_format("torch", columns=["input_ids", "attention_mask", "label"])
    eval_dataset.set_format("torch", columns=["input_ids", "attention_mask", "label"])

    # Trainer 설정
    training_args = TrainingArguments(
        output_dir=output_dir,
        evaluation_strategy="epoch",
        save_strategy="epoch",
        num_train_epochs=num_train_epochs,
        per_device_train_batch_size=batch_size,
        per_device_eval_batch_size=batch_size,
        learning_rate=learning_rate,
        weight_decay=0.01,
        logging_dir='./logs',
        save_total_limit=2
    )

    trainer = Trainer(
        model=model,
        args=training_args,
        train_dataset=train_dataset,
        eval_dataset=eval_dataset,
        tokenizer=tokenizer
    )

    console.print("[cyan]파인 튜닝 시작...[/cyan]")
    trainer.train()
    console.print("[green]파인 튜닝 완료![/green]")

    # 모델/토크나이저 저장
    model.save_pretrained(output_dir)
    tokenizer.save_pretrained(output_dir)
    console.print(f"[bold green]파인 튜닝된 모델이 '{output_dir}'에 저장되었습니다.[/bold green]")

# ----------------------------------------
# 2) AI 모델 초기화 (CodeBERT 기반)
# ----------------------------------------
def initialize_ai_model():
    """
    위에서 파인 튜닝한 모델(또는 사전학습만 된 CodeBERT)을 로드합니다.
    FINETUNED_MODEL_DIR 경로에 모델이 없다면 기본 CodeBERT 로드.
    """
    try:
        if os.path.exists(FINETUNED_MODEL_DIR):
            console.print("[cyan]파인 튜닝된 CodeBERT 모델 로딩 중...[/cyan]")
            model_name = FINETUNED_MODEL_DIR
        else:
            console.print("[yellow]파인 튜닝된 모델을 찾지 못했습니다. 사전학습 모델만 사용합니다.[/yellow]")
            model_name = "microsoft/codebert-base"

        tokenizer = AutoTokenizer.from_pretrained(model_name)
        model = AutoModelForSequenceClassification.from_pretrained(model_name, num_labels=2)
        return tokenizer, model
    except Exception as e:
        console.print(f"[bold red]AI 모델 초기화 실패: {str(e)}[/bold red]")
        return None, None

# ----------------------------------------
# 3) AI 기반 분석 함수
# ----------------------------------------
def analyze_code_with_ai(code_snippet, tokenizer, model):
    try:
        inputs = tokenizer(code_snippet, return_tensors="pt", truncation=True, max_length=512)
        with torch.no_grad():
            outputs = model(**inputs)
            predictions = torch.softmax(outputs.logits, dim=1)
            vulnerability_score = predictions[0][1].item()
        return vulnerability_score > 0.5  # 0.5 이상이면 취약
    except Exception as e:
        console.print(f"[yellow]AI 분석 중 오류 발생: {str(e)}[/yellow]")
        return False

# ----------------------------------------
# 4) 정적 분석(패턴) 및 데이터 흐름 검사
# ----------------------------------------
def find_input_sources(content):
    input_patterns = {
        'GET': r'\$_GET\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'POST': r'\$_POST\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'REQUEST': r'\$_REQUEST\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'COOKIE': r'\$_COOKIE\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'FILES': r'\$_FILES\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]',
        'SERVER': r'\$_SERVER\s*\[\s*[\'"]HTTP_([^\'"]+)[\'"]\s*\]',
        'wp_query': r'get_query_var\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)'
    }
    
    from collections import defaultdict
    inputs = defaultdict(list)
    for source, pattern in input_patterns.items():
        matches = re.finditer(pattern, content)
        for match in matches:
            param_name = match.group(1)
            inputs[source].append(param_name)
    return inputs

def find_variable_assignments(content):
    var_assignments = {}
    assignment_pattern = r'\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*(.*?);'
    
    for match in re.finditer(assignment_pattern, content):
        var_name = match.group(1)
        var_value = match.group(2).strip()
        var_assignments[var_name] = var_value
    return var_assignments

def find_sinks(content, file_path):
    sink_patterns = [
        (r'echo\s+((?:[^;]|(?:\\\;))+);', 'echo'),
        (r'print\s+((?:[^;]|(?:\\\;))+);', 'print'),
        (r'<\?=\s*((?:[^\?]|(?:\\\?))+)\?>', '<?='),
        (r'printf\s*\(\s*([^,\)]+)(?:,|\))', 'printf'),
        (r'\.innerHTML\s*=\s*(.*?);', 'innerHTML'),
        (r'\.outerHTML\s*=\s*(.*?);', 'outerHTML'),
        (r'\.insertAdjacentHTML\s*\(\s*[\'"][^\'"]*[\'"]\s*,\s*(.*?)\)', 'insertAdjacentHTML'),
        (r'document\.write\s*\((.*?)\)', 'document.write'),
        (r'\.html\s*\(\s*(.*?)\s*\)', 'jQuery.html()'),
        (r'\.append\s*\(\s*(.*?)\s*\)', 'jQuery.append()'),
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
    sanitization_functions = [
        r'htmlspecialchars\s*\(', r'htmlentities\s*\(', r'strip_tags\s*\(',
        r'addslashes\s*\(', r'stripslashes\s*\(', r'intval\s*\(', r'floatval\s*\(',
        r'abs\s*\(', r'filter_var\s*\([^,]+,\s*FILTER_SANITIZE_',
        r'esc_html\s*\(', r'esc_attr\s*\(', r'esc_url\s*\(', r'esc_js\s*\(',
        r'esc_textarea\s*\(', r'sanitize_text_field\s*\(', r'sanitize_title\s*\(',
        r'sanitize_email\s*\(', r'wp_kses\s*\(', r'wp_kses_post\s*\(', r'absint\s*\('
    ]
    for func_pattern in sanitization_functions:
        if re.search(func_pattern, code):
            return True
    return False

def check_dom_xss(content):
    dom_xss_patterns = [
        r'document\.URL', r'document\.documentURI', r'document\.location', r'document\.referrer',
        r'window\.name', r'location\.hash', r'location\.search', r'location\.href'
    ]
    dom_xss_sinks = [
        r'\.innerHTML', r'\.outerHTML', r'\.insertAdjacentHTML', r'document\.write',
        r'document\.writeln', r'eval\(', r'setTimeout\(', r'setInterval\(', r'new\s+Function\('
    ]
    for source in dom_xss_patterns:
        for sink in dom_xss_sinks:
            pattern = f"{source}[\\s\\S]*?{sink}"
            if re.search(pattern, content):
                return True
    return False

def trace_data_flow(content, inputs, sinks, var_assignments):
    vulnerabilities = []
    
    var_graph = {}
    for var_name, var_value in var_assignments.items():
        var_graph[var_name] = var_value
    
    input_vars = {}
    for source, params in inputs.items():
        for param in params:
            source_pattern = fr'\$_{source}\s*\[\s*[\'"]?{param}[\'"]?\s*\]'
            if source == 'wp_query':
                source_pattern = fr'get_query_var\s*\(\s*[\'"]?{param}[\'"]?\s*\)'
            for var_name, var_value in var_assignments.items():
                if re.search(source_pattern, var_value):
                    input_vars[var_name] = (source, param)
    
    # 변수 의존성(최대 3단계)
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
        
        # 직접 입력 소스
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
        
        # 변수 통한 간접
        for var_name in var_graph:
            var_pattern = fr'\${var_name}\b'
            if re.search(var_pattern, sink_content):
                if var_name in input_vars and not sanitized:
                    source, param = input_vars[var_name]
                    is_vulnerable = True
                    vulnerable_source = f'{source}["{param}"] -> ${var_name}'
        
        if is_vulnerable:
            vulnerabilities.append({
                'source': vulnerable_source,
                'sink': sink,
                'description': f'사용자 입력 {vulnerable_source}이(가) {sink["type"]}에서 필터링 없이 출력됨',
                'severity': 'High' if ('innerHTML' in sink['type'] or 'document.write' in sink['type']) else 'Medium'
            })
    
    # DOM XSS
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

# ----------------------------------------
# 5) PHP 파일 단위 분석
# ----------------------------------------
def analyze_php_file(file_path, relative_path, tokenizer=None, model=None):
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # 1) 입력 찾기
        inputs = find_input_sources(content)
        # 2) 변수 할당 찾기
        var_assignments = find_variable_assignments(content)
        # 3) 싱크 찾기
        sinks = find_sinks(content, relative_path)
        # 4) 패턴 기반
        pattern_vulnerabilities = find_vulnerable_patterns(content)
        # 5) AI 기반
        ai_vulnerabilities = []
        if tokenizer and model:
            # 코드를 512 바이트(또는 문자) 단위로 나눠서 AI 분석
            # 실제론 토큰 단위가 정확하지만 여기선 간단히 처리
            step = 512
            for i in range(0, len(content), step):
                chunk = content[i:i+step]
                if analyze_code_with_ai(chunk, tokenizer, model):
                    ai_vulnerabilities.append({
                        'type': 'AI Detected',
                        'content': chunk[:100] + '...',  # 100자만 미리보기
                        'line': content[:i].count('\n') + 1
                    })

        # 6) 데이터 흐름 추적
        vulnerabilities = trace_data_flow(content, inputs, sinks, var_assignments)
        
        # 종합
        all_vulnerabilities = vulnerabilities + pattern_vulnerabilities + ai_vulnerabilities
        
        return inputs, sinks, all_vulnerabilities
    except Exception as e:
        console.print(f"[bold red]파일 분석 중 오류 발생: {file_path} - {str(e)}[/bold red]")
        return {}, [], []

# ----------------------------------------
# 6) 플러그인 ZIP 스캔
# ----------------------------------------
def scan_plugin(plugin_path, progress=None, task_id=None, tokenizer=None, model=None):
    from collections import defaultdict
    all_vulnerabilities = []
    all_inputs = defaultdict(list)
    all_sinks = []
    vulnerable_files = []
    
    plugin_name = os.path.basename(plugin_path)[:-4]
    
    if progress and task_id is not None:
        progress.update(task_id, description=f"[cyan]분석 중: {plugin_name}[/cyan]")
    
    # ZIP 해제
    with zipfile.ZipFile(plugin_path, 'r') as zip_ref:
        temp_dir = os.path.join('temp', plugin_name)
        zip_ref.extractall(temp_dir)
        
        # php 파일 찾기
        php_files = []
        for root, _, files in os.walk(temp_dir):
            for file in files:
                if file.endswith('.php'):
                    relp = os.path.relpath(os.path.join(root, file), temp_dir)
                    php_files.append((os.path.join(root, file), relp))
        
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
    
    # 임시 디렉토리 삭제
    shutil.rmtree(temp_dir)
    
    # 결과 처리
    if all_vulnerabilities:
        success_dir = './success'
        if not os.path.exists(success_dir):
            os.makedirs(success_dir)
        
        # 취약 플러그인을 success 폴더로 이동
        shutil.copy2(plugin_path, os.path.join(success_dir, os.path.basename(plugin_path)))
        
        result = {
            'plugin': plugin_name,
            'inputs': dict(all_inputs),
            'vulnerabilities': all_vulnerabilities,
            'vulnerable_files': vulnerable_files,
            'total_vulnerabilities': len(all_vulnerabilities)
        }
        
        append_to_results(result)
        return True, result
    else:
        # 취약점이 없으면 원본 ZIP 삭제
        os.remove(plugin_path)
        return False, None

def append_to_results(result):
    if os.path.exists(RESULT_JSON_FILE):
        try:
            with open(RESULT_JSON_FILE, 'r', encoding='utf-8') as f:
                data = json.load(f)
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
    
    all_results.append(result)
    with open(RESULT_JSON_FILE, 'w', encoding='utf-8') as f:
        json.dump(all_results, f, indent=2, ensure_ascii=False)

def display_vulnerability_details(result):
    console.print(Panel(f"[bold yellow]플러그인: {result['plugin']}[/bold yellow]", expand=False))
    console.print("[bold cyan]취약한 파일:[/bold cyan]")
    for file in result['vulnerable_files']:
        console.print(f"  [yellow]• {file}[/yellow]")
    
    table = Table(title=f"발견된 XSS 취약점 ({result['total_vulnerabilities']}개)")
    table.add_column("파일", style="cyan")
    table.add_column("라인", style="blue")
    table.add_column("유형", style="magenta")
    table.add_column("소스", style="green")
    table.add_column("심각도", style="yellow")
    
    for vuln in result['vulnerabilities']:
        # AI Detected 등의 항목엔 sink가 없을 수도 있으므로 예외 처리
        sink_file = vuln['sink']['file'] if 'sink' in vuln and 'file' in vuln['sink'] else "-"
        sink_line = str(vuln['sink']['line']) if 'sink' in vuln and 'line' in vuln['sink'] else "-"
        sink_type = vuln['sink']['type'] if 'sink' in vuln and 'type' in vuln['sink'] else vuln.get('type', 'AI Detected')
        source = vuln.get('source', '-')
        severity = vuln.get('severity', 'N/A')
        
        table.add_row(sink_file, sink_line, sink_type, source, severity)
    
    console.print(table)

    console.print("[bold cyan]취약한 코드 샘플 (최대 3개):[/bold cyan]")
    count = 0
    for vuln in result['vulnerabilities']:
        if 'sink' in vuln and 'context' in vuln['sink']:
            if count >= 3:
                break
            sink = vuln['sink']
            console.print(f"[bold yellow]취약점 #{count+1} ({sink['file']}:{sink['line']})[/bold yellow]")
            syntax = Syntax(sink['context'], "php", theme="monokai", line_numbers=True)
            console.print(syntax)
            count += 1
    console.print("\n")

# ----------------------------------------
# 7) 메인 실행
# ----------------------------------------
def main():
    # (선택) 먼저 파인 튜닝을 진행하고 싶다면 아래 주석을 해제하세요
    # train_codebert_xss(dataset_path="xss_dataset.json")

    plugins_folder = './plugins'
    success_folder = './success'
    
    # 폴더 준비
    for folder in [plugins_folder, success_folder, 'temp']:
        if not os.path.exists(folder):
            os.makedirs(folder)

    # AI 모델 로드
    console.print("[cyan]AI 모델을 초기화하는 중...[/cyan]")
    tokenizer, model = initialize_ai_model()
    
    if tokenizer and model:
        console.print("[green]AI 모델 초기화 완료![/green]")
    else:
        console.print("[yellow]AI 모델을 사용하지 않고 패턴 기반 분석만 수행합니다.[/yellow]")

    # 플러그인 목록
    plugin_files = [f for f in os.listdir(plugins_folder) if f.endswith('.zip')]

    if not plugin_files:
        console.print("[bold red]분석할 플러그인이 없습니다. './plugins' 폴더에 ZIP 파일을 넣어주세요.[/bold red]")
        return

    console.print(Panel("[bold green]WordPress 플러그인 XSS 취약점 탐지기[/bold green]", subtitle="CodeBERT + 정적분석"))
    console.print(f"[cyan]총 {len(plugin_files)}개의 플러그인을 분석합니다...[/cyan]\n")

    vulnerable_plugins = []

    # 진행 상황 표시
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
        
            if has_vulnerabilities and result:
                vulnerable_plugins.append(result)
        
            progress.update(overall_task, advance=1)
            progress.remove_task(task_id)

    # 결과 요약
    console.print("\n[bold green]분석 완료![/bold green]\n")

    if vulnerable_plugins:
        console.print(Panel(f"[bold red]취약점이 발견된 플러그인: {len(vulnerable_plugins)}개[/bold red]", expand=False))
        
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
        
        console.print("[bold yellow]취약점 상세 정보를 표시하시겠습니까? (y/n)[/bold yellow]", end=" ")
        choice = input().strip().lower()
        if choice == 'y':
            for result in vulnerable_plugins:
                display_vulnerability_details(result)
    else:
        console.print("[green]취약점이 발견된 플러그인이 없습니다.[/green]")

    if os.path.exists('temp'):
        shutil.rmtree('temp')

if __name__ == "__main__":
    main()
