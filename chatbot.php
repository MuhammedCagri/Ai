<?php
require_once("openAi.php");
require_once("callbackFuncs.php");

$Ai = new OpenAIService(null);

//Func class
$callBackFunctions = new CallBackFunctions();

//Func defination schema
$functions = [
                    [
                        'name' => 'downloadData',
                        'description' => 'function that will run when the user wants to download data',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'size' => [
                                    'type' => 'integer',
                                    'description' => 'Size of data'
                                ]
                            ],
                            'required' => ['size']
                        ]
                    ]
];
                



    // Ai Options
    $Ai->setApiKey('Your_Api_Key');
    $Ai->setPrompt('What monkeys like');
    $Ai->setDocument([['role'=>'assistant','content'=>'Document: Monkeys only loves bananas']]); // document tag is related to my system definitions
    $Ai->setAnswerCount(1);
    $Ai->setTemperature(0.2);

    $Ai->setFunctions($functions);
    $Ai->setFunctionClass($callBackFunctions);
    


    //you can also use gpt-4o but sometimes it misfires when calling a function.
    $response = $Ai->getChatGptAnswers("gpt-4-turbo-preview");
    $response = $response['choices'][0]['message']['content'];
    print_r($response);


?>