<?php

require_once("/openAi.php"); 

$Ai = new OpenAIService(null);

$Ai->setApiKey('YOUR_API_KEY');

$response = $Ai->getTranscription($_FILES);

$result = json_decode($response,true);

echo json_encode(['transcription'=>$result['text']]);