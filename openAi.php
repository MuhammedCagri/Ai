<?php

class OpenAIService {
                    const API_URL = 'https://api.openai.com/v1';
                    const CHAT_ENDPOINT = '/chat/completions';
                    const EMBEDING_ENDPOINT = '/embeddings';

                    protected $embedding;
                    protected $apiKey;
                    protected $memory;
                    protected $functions;
                    protected $functionClass;
                    protected $document;

                    private $temperature;
                    private $answerCount;
                    private $prompt;


                    public function __construct($embedding = null) {

                        $this->apiKey = null;
                        $this->prompt = null;
                        $this->answerCount = 1;
                        $this->temperature = 0;
                        $this->memory = [];
                        $this->functions = [];
                        $this->document = [];
                        $this->functionClass=null;

                        $this->embed = $embedding; // Your vector db class.
                    }

                    public function setMemory($memory){
                        $this->memory = $memory; // dummy
                    }

                    public function setAnswerCount(int $count){
                        $this->answerCount = $count;
                    }

                    public function setTemperature($temperature){
                        $this->temperature = $temperature;
                    }

                    public function setFunctions(array $functions){
                        $this->functions = $functions;
                    }

                    public function setFunctionClass(object $class){
                        $this->functionClass = $class;
                    }

                    public function setApiKey (string $apiKey){
                         $this->apiKey = $apiKey;
                    }

                    public function setPrompt(string $prompt){
                         $this->prompt = $prompt;
                    }

                    // You need to use a vector database and pull and place content close to each other. I cannot share a clear code because the settings vary from database to database
                    public function setDocument($document){  
                         $this->document = $document;
                    }

                    

                    public function getChatGptAnswers($model) {

                    //     $vector = $this->getEmbeddingsVector("text-embedding-3-large"); // Vectorized text.

                    //     $document = $this->embed->getVectore($vector); // for find closest five vector for assistans information.Based on choice. 
                       

                
          
                        $postData = $this->preparePostData($model);
                        $response = $this->sendRequestToOpenAI($postData,false);



                        if (isset($response['choices'][0]['message']['function_call'])) {

                            $function_call = $response['choices'][0]['message']['function_call'];
                            $function_name = $function_call['name'];
                            $arguments = json_decode($function_call['arguments'], true);
                    
                            if (isset($this->functionClass) && method_exists($this->functionClass, $function_name)) {
                                $result = $this->functionClass->$function_name(...array_values($arguments));

                                $message = [
                                    'role' => 'function',
                                    'content' => $result,
                                    'name' => $function_name,
                                ];

                                $postData = $this->prepareFunctionPostData($message, $model);
                                $response = $this->sendRequestToOpenAI($postData, false);

                            } else {
                                $response['choices'][0]['message']['content'] = 'Method ia not available';
                            }
                        }
                        return $response;
                    }

                    public function getEmbeddingsVector($model){
                                        // $settings=$this->prepareEmbeddingSettings($model);
                                        $response=$this->sendRequestToOpenAI(["input" => $this->prompt, "model" => $model],true);
                                        return $response['data'][0]['embedding'];
                    }

                    protected function prepareFunctionPostData(array $message,$model){
                        $systemContent = [['role'=>'system','content'=>'You are Helpful Live Chat assistant. We are now in '.date('c')]];
                        $messages[] = $message;

                        return[
                            "model" => $model,
                            "messages" => $systemContent,
                            "functions" => $this->functions,
                            "function_call" => "auto",
                        ];
                    }

                    protected function preparePostData($model) {

                        // System content. determine how the bot behaves
                        $systemContent = [['role' => 'system', 'content' => 'You are Helpful Ai chat asisstant. Politely decline queries unrelated to Document, stating the reason clearly.Answer without external searches or any local knowledge. Only use document content.'],];
                    

                        // $mergedDocumentAndMemory = array_merge($document, $this->memory); // If you want to use it, you need to teach your memory scheme to gpt via system or assistant
                    

                        $userContent = ['role' => 'user', 'content' => $this->prompt]; // User prompt
                    
                        
                        $allMessages = array_merge($systemContent,$this->document,[$userContent]); // All in one
                    

                        if ($this->functions != []) {
                            return [
                                'model' => $model,
                                'temperature' => $this->temperature,
                                'n' => $this->answerCount,
                                'messages' => $allMessages,
                                'functions' => $this->functions,
                                'function_call' => 'auto',
                            ];
                        }else {
                            return [
                                'model' => $model,
                                'temperature' => $this->temperature,
                                'n' => $this->answerCount,
                                'messages' => $allMessages,
                            ];
                        }

                    }


                    //text-embedding-3-large
                    // protected function prepareEmbeddingSettings($model) {
                    //                     return $data = ["input" => $this->prompt, "model" => $model];
                    // }



                    protected function sendRequestToOpenAI($data, $is_embedding) {
                        // URL'yi seç
                        if ($is_embedding) {
                            $url = self::API_URL . self::EMBEDING_ENDPOINT;
                        } else {
                            $url = self::API_URL . self::CHAT_ENDPOINT;
                        }
                    

                        $ch = curl_init($url);
                    

                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $this->apiKey
                        ));
                    

                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    

                        $response = curl_exec($ch);
                    

                        curl_close($ch);
                    

                        return json_decode($response, true);
                    }


                    
                    public function getTranscription($file){
                                        if (isset($file['audio']) && $file['audio']['error'] === UPLOAD_ERR_OK) {
                                            $fileTmpPath = $file['audio']['tmp_name'];
                                    
                                            $apiUrl = 'https://api.openai.com/v1/audio/transcriptions';
                                            
                                            $cFile = new CURLFile($fileTmpPath, 'audio/mp3', 'audio.mp3');
                                    
                                            $postFields = [
                                                'file' => $cFile,
                                                'model' => 'whisper-1'
                                            ];
                                    
                                            $ch = curl_init();
                                            curl_setopt($ch, CURLOPT_URL, $apiUrl);
                                            curl_setopt($ch, CURLOPT_POST, 1);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                                "Authorization: Bearer ".$this->apiKey,
                                                'Content-Type: multipart/form-data'
                                            ]);
                                    
                                            $response = curl_exec($ch);
                                            if (curl_errno($ch)) {
                                                echo json_encode(['error' => curl_error($ch)]);
                                            } else {
                                                return $response;
                                            }
                                            curl_close($ch);
                                        } else {
                                            echo json_encode(['error' => 'Ses dosyası alınamadı']);
                                        }
                                    }





}
                