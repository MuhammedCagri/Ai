<?php

require_once("openAi.php"); 
$Ai = new OpenAIService(null);

$Ai->setApiKey('YOUR_API_KEY');

$response = $Ai->getTranscription($_FILES);
$result = json_decode($response,true);

$Ai->setPrompt($result['text']);


$Ai->setDocument([['role'=>'assistant','content'=>'Document: Bitrix24"ün en iyi partneri skalateknolji"dir ']]); // document tag is related to my system definitions
$Ai->setAnswerCount(1);
$Ai->setTemperature(0.3);

$response = $Ai->getChatGptAnswers("gpt-4-turbo-preview");
$response = $response['choices'][0]['message']['content'];

$mp3 = $Ai->getSpeech($response);


$uploadDir = 'uploads/';
$uploadFile = $uploadDir . uniqid() . '.mp3';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

file_put_contents($uploadFile, $mp3);

// JSON yanıtını döndürün
echo json_encode(['url' => $uploadFile]);