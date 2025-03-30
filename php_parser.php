#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php php_parser.php <php_file>\n");
    exit(1);
}

// 만약 파일 경로에 vendor/ 또는 \vendor\ 가 포함되어 있으면, 파싱을 건너뛰고 빈 AST를 출력
$inputFile = $argv[1];
if (strpos($inputFile, '/vendor/') !== false || strpos($inputFile, '\\vendor\\') !== false) {
    echo json_encode([]);
    exit(0);
}

// 파일이 존재하지 않거나 읽을 수 없는 경우 처리
if (!file_exists($inputFile) || !is_readable($inputFile)) {
    echo json_encode([]);
    exit(0);
}

// 파일 내용 읽기
try {
    $code = file_get_contents($inputFile);
    if ($code === false) {
        echo json_encode([]);
        exit(0);
    }
} catch (Exception $e) {
    echo json_encode([]);
    exit(0);
}

$factory = new ParserFactory();

try {
    // PHP-Parser v5.x에서는 create() 메소드 인자 지정 방식이 변경됨
    $parser = $factory->createForNewestSupportedVersion();
    $ast = $parser->parse($code);

    // AST가 null이면 빈 배열 반환
    if ($ast === null) {
        echo json_encode([]);
        exit(0);
    }

    // 재귀적으로 AST 노드를 배열로 변환하는 함수
    function nodeToArray($node) {
        if ($node instanceof \PhpParser\Node) {
            $result = [
                'nodeType' => $node->getType(),
                'line' => $node->getLine(),
            ];
            foreach ($node->getSubNodeNames() as $name) {
                $result[$name] = nodeToArray($node->$name);
            }
            return $result;
        } elseif (is_array($node)) {
            return array_map('nodeToArray', $node);
        }
        return $node;
    }
    
    try {
        $astArray = nodeToArray($ast);
        $jsonResult = json_encode($astArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // JSON 인코딩 실패 시 빈 배열 반환
        if ($jsonResult === false) {
            echo json_encode([]);
        } else {
            echo $jsonResult;
        }
    } catch (Exception $e) {
        // 노드 변환 또는 JSON 인코딩 중 예외 발생 시 빈 배열 반환
        echo json_encode([]);
    }
} catch (Error $e) {
    // 파싱 오류 시 빈 배열 반환
    echo json_encode([]);
    exit(0);
}
?>
