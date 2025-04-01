import concurrent.futures
import os
import requests
from bs4 import BeautifulSoup
import time
import random
from urllib3.util import Retry
from requests.adapters import HTTPAdapter

colors = [
    '\033[91m',  # RED
    '\033[92m',  # GREEN
    '\033[93m',  # YELLOW
    '\033[94m',  # BLUE
    '\033[95m',  # MAGENTA
    '\033[96m',  # CYAN
    '\033[90m',  # DARK GRAY
    '\033[97m',  # WHITE
    '\033[91m',  # LIGHT RED
    '\033[92m'   # LIGHT GREEN
]

RESET = '\033[0m'

save_dir = "./plugins"

def ensure_directory(directory):
    if not os.path.exists(directory):
        os.makedirs(directory)

def get_existing_folders(save_dir):
    if not os.path.exists(save_dir):
        return []
    existing_folders = []
    for folder_name in os.listdir(save_dir):
        if os.path.isdir(os.path.join(save_dir, folder_name)):
            existing_folders.append(folder_name)
    return existing_folders

def create_session():
    session = requests.Session()
    # 브라우저처럼 보이도록 User-Agent 설정
    session.headers.update({
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                      "(KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3"
    })
    # 재시도 정책 설정 (429, 500, 502, 503, 504 오류 발생 시 최대 5회 재시도)
    retries = Retry(total=5, backoff_factor=1, status_forcelist=[429, 500, 502, 503, 504])
    adapter = HTTPAdapter(max_retries=retries)
    session.mount("http://", adapter)
    session.mount("https://", adapter)
    return session

def download_plugin(link, existing_folders, session):
    try:
        response = session.get(link, timeout=10)
        response.raise_for_status()
    except Exception as e:
        print(f"Error fetching plugin page {link}: {e}")
        return 0

    soup = BeautifulSoup(response.content, 'html.parser')
    
    # 기존 방식: 특정 클래스의 a 태그 찾기
    download_anchor = soup.find('a', {'class': 'plugin-download button download-button button-large'})
    
    # 새로운 방식: 다운로드 링크 패턴으로 찾기
    if not download_anchor:
        # 모든 a 태그를 순회하며 href에 다운로드 패턴이 있는지 검사
        for a in soup.find_all('a', href=True):
            href = a['href']
            if href.startswith("https://downloads.wordpress.org/plugin/") and href.endswith(".zip"):
                download_anchor = a
                break

    if not download_anchor:
        print(f"Download link not found for {link}")
        return 0

    download_link = download_anchor['href']
    file_name = download_link.split('/')[-1]
    folder_name = file_name.rsplit('.', 1)[0]

    if folder_name in existing_folders:
        print(f"Skipping {folder_name} as it already exists.")
        return 0

    save_path = os.path.join(save_dir, file_name)
    try:
        download_response = session.get(download_link, timeout=10)
        download_response.raise_for_status()
        with open(save_path, 'wb') as f:
            f.write(download_response.content)
        print('Downloaded:', file_name)
    except Exception as e:
        print(f"Error downloading plugin from {download_link}: {e}")

    # 다운로드 후 짧은 지연 추가
    time.sleep(random.uniform(0.5, 2))
    return 1


def download_plugins_on_page(page_num, existing_folders, target, session):
    base_url = "https://ko.wordpress.org/plugins/search/{}/page/".format(target)
    url = base_url + str(page_num)
    try:
        response = session.get(url, timeout=10)
        response.raise_for_status()
    except Exception as e:
        print(f"Error fetching search page {url}: {e}")
        return []

    soup = BeautifulSoup(response.content, 'html.parser')
    plugin_entries = soup.find_all('h3', {'class': 'entry-title'})
    if not plugin_entries:
        return []

    links = [entry.find('a')['href'] for entry in plugin_entries if entry.find('a')]
    
    # 내부 스레드풀 동시 요청 수를 줄임 (max_workers=3)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        futures = [executor.submit(download_plugin, link, existing_folders, session) for link in links]
        for future in concurrent.futures.as_completed(futures):
            pass
    # 페이지당 요청 후 잠깐 지연 추가
    time.sleep(random.uniform(1, 3))
    return links

def download_plugins_for_target(target, existing_folders, color_code, session):
    page_num = 1
    while True:
        links = download_plugins_on_page(page_num, existing_folders, target, session)
        if not links:
            break
        print(f'{color_code}Downloaded {len(links)} plugins from page {page_num} for {target}.{RESET}')
        page_num += 1
        if page_num > 50:
            break

if __name__ == "__main__":
    ensure_directory(save_dir)
    targets = input(">> ").split()[:10]  # 최대 10개 타겟
    existing_folders = get_existing_folders(save_dir)

    print("아래의 키워드에 대한 플러그인을 다운로드 합니다.")
    print("-------------------------------------------------")
    for i, target in enumerate(targets):
        print(f'{i+1}. {colors[i % len(colors)]}{target}{RESET}')
    print("-------------------------------------------------\n")

    session = create_session()
    # 외부 스레드풀 동시 요청 수를 줄임 (max_workers=3)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        futures = [
            executor.submit(download_plugins_for_target, target, existing_folders, colors[i % len(colors)], session)
            for i, target in enumerate(targets)
        ]
        for future in concurrent.futures.as_completed(futures):
            pass
