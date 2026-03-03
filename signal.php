<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$dir = __DIR__ . '/signals';
if (!is_dir($dir)) mkdir($dir, 0755, true);
foreach (glob("$dir/*.json") as $f) if (time() - filemtime($f) > 120) unlink($f);

$room = preg_replace('/[^A-Za-z0-9]/', '', $_REQUEST['room'] ?? '');
if (!$room) { echo json_encode(['error' => 'no room']); exit; }
$file = "$dir/$room.json";

function rd($f) { return file_exists($f) ? json_decode(file_get_contents($f), true) : ['offers'=>[],'answers'=>[],'hCand'=>[],'vCand'=>[]]; }
function wr($f, $d) { file_put_contents($f, json_encode($d), LOCK_EX); }

$action = $_REQUEST['action'] ?? 'poll';
switch ($action) {
    case 'offer':
        $in = json_decode(file_get_contents('php://input'), true);
        $vid = $in['viewerId'] ?? uniqid();
        $d = rd($file); $d['offers'][$vid] = $in; wr($file, $d);
        echo json_encode(['success' => true, 'viewerId' => $vid]);
        break;
    case 'answer':
        $in = json_decode(file_get_contents('php://input'), true);
        $vid = $in['viewerId'] ?? '';
        $d = rd($file); $d['answers'][$vid] = $in; wr($file, $d);
        echo json_encode(['success' => true]);
        break;
    case 'candidate':
        $in = json_decode(file_get_contents('php://input'), true);
        $from = $in['from'] ?? 'host'; $vid = $in['viewerId'] ?? 'x';
        $d = rd($file);
        if ($from === 'viewer') $d['vCand'][$vid][] = $in['candidate'];
        else $d['hCand'][$vid][] = $in['candidate'];
        wr($file, $d);
        echo json_encode(['success' => true]);
        break;
    case 'poll':
        $from = $_GET['from'] ?? 'host'; $vid = $_GET['viewerId'] ?? '';
        $d = rd($file); $r = [];
        if ($from === 'host') {
            foreach ($d['offers'] as $v => $o) { $r['offer'] = $o; $r['offer']['viewerId'] = $v; unset($d['offers'][$v]); wr($file, $d); break; }
            foreach ($d['vCand'] as $v => $c) { if (!empty($c)) { $r['candidate'] = ['viewerId'=>$v,'candidate'=>array_shift($c)]; $d['vCand'][$v] = $c; wr($file, $d); break; } }
        } else {
            if ($vid && isset($d['answers'][$vid])) { $r['answer'] = $d['answers'][$vid]; unset($d['answers'][$vid]); wr($file, $d); }
            if ($vid && !empty($d['hCand'][$vid])) { $r['candidate'] = array_shift($d['hCand'][$vid]); wr($file, $d); }
        }
        echo json_encode($r);
        break;
    case 'reset':
        if (file_exists($file)) unlink($file);
        echo json_encode(['success' => true]);
        break;
}
