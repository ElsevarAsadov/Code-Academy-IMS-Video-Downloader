import ffmpeg
import requests as rq
from bs4 import BeautifulSoup
from uuid import uuid4
from dotenv import load_dotenv
from os import getenv, getcwd, remove
from os.path import join
from sys import argv
from time import sleep


def mb2kb(kb):
    return kb * 1024


load_dotenv("../../.env")

DO_COMPRESS = False

CHUNK_SIZE = int(getenv('CHUNK_SIZE'))
MAX_EXCEPTION_LIMIT = int(getenv('MAX_EXCEPTION_LIMIT'))
VIDEO_CACHE_FOLDER_PATH = getenv("VIDEO_CACHE_FOLDER_PATH")
"""

this python process will call from other process like this

SCRIPT_NAME.PY + COOKIE + URL

"""

try:
    COOKIE = argv[1]
    URL = argv[2]
except Exception:
    # send error exit code.
    exit(2)

HEADER = {
    "Cookie": COOKIE,
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
}


html_content = rq.get(URL, headers=HEADER).content
soup = BeautifulSoup(html_content, 'html.parser')
iframe_tag = soup.find('iframe', recursive=True)
iframe_content = rq.get(iframe_tag.attrs['src'], headers=HEADER).content
iframe_soup = BeautifulSoup(iframe_content, 'html.parser')
source = iframe_soup.find("source", recursive=True)

VIDEO_URL = source.attrs['src']


OUTPUT_FILE_NAME = f"{uuid4()}.mp4"
COMPRESSED_FILE_NAME = f"{uuid4()}.mp4"
FILE_PATH = join(getcwd(), "..", "..", VIDEO_CACHE_FOLDER_PATH)

OUTPUT_FILE_PATH = join(FILE_PATH, OUTPUT_FILE_NAME)
COMPRESSED_FILE_PATH = join(FILE_PATH, COMPRESSED_FILE_NAME)

exception_count = 0
while exception_count < MAX_EXCEPTION_LIMIT:
    try:
        response = rq.get(VIDEO_URL, stream=True, headers=HEADER)

        #size of video.
        #print(response.headers.get('Content-Length'))

        with open(OUTPUT_FILE_PATH, 'wb') as f:
            for chunk in response.iter_content(chunk_size=CHUNK_SIZE):
                f.write(chunk)

        if DO_COMPRESS: ffmpeg.input(OUTPUT_FILE_PATH).output(COMPRESSED_FILE_PATH, b='500k').run()
        print(OUTPUT_FILE_PATH)
        exit(0)
    except Exception as e:
        exception_count += 1
        sleep(2)


# send file path to caller process to delete cache
remove(OUTPUT_FILE_PATH)
exit(1)
