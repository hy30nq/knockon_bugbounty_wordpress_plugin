# WordPress 플러그인 XSS 취약점 탐지기

WordPress 플러그인의 XSS(Cross-Site Scripting) 취약점을 자동으로 탐지하는 도구입니다. CodeBERT AI 모델과 정적 분석을 결합하여 취약점을 탐지합니다.

## 주요 기능

- WordPress 플러그인 자동 다운로드
- AI 기반 취약점 분석 (CodeBERT)
- 정적 코드 분석
- 패턴 기반 취약점 탐지
- 상세한 취약점 보고서 생성

## 설치 방법

1. 필요한 패키지 설치:
```bash
pip install -r requirements.txt
```

## 실행 방법

1. 플러그인 다운로드:
```bash
python download.py
```
실행 후 검색하고 싶은 키워드를 입력하세요 (최대 10개).

2. 취약점 분석:
```bash
python detect_xss_ai.py
```
다운로드된 플러그인들의 XSS 취약점을 분석합니다.

## 결과

- 취약점이 발견된 플러그인은 `./success` 폴더에 저장됩니다.
- 상세 분석 결과는 `wordpress_xss_vulnerabilities.json` 파일에 저장됩니다.
- 분석 중 진행 상황이 실시간으로 표시됩니다.

## 시스템 요구사항

- Python 3.8 이상
- CUDA 지원 GPU (선택사항, AI 분석 속도 향상을 위해 권장)

## 주의사항

- 이 도구는 교육 및 연구 목적으로만 사용해야 합니다.
- 실제 웹사이트에서 무단으로 사용하지 마세요.
- 발견된 취약점은 해당 플러그인 개발자에게 보고하세요.

## 라이선스

MIT License 