<?php
include 'crest.php';
if (isset($_REQUEST["PLACEMENT_OPTIONS"]) && $_REQUEST["PLACEMENT"]=="SETTING_CONNECTOR") {
    $parameters = json_decode($_REQUEST["PLACEMENT_OPTIONS"], true);

    if ($parameters["CONNECTOR"] && $parameters["LINE"]) {
        $parameters = [
            "CONNECTOR" => $parameters["CONNECTOR"],
            "LINE" => $parameters["LINE"],
            "ACTIVE" => $parameters["ACTIVE_STATUS"],
        ];
        $connectorActivating = CRest::call("imconnector.activate", $parameters);
        CRest::call("app.option.set", ["options" => ["openlineID" => $parameters["LINE"]]]);

    }

?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
          crossorigin="anonymous">
    <div class="alert alert-success mt-3" role="alert">
        Открытая линия Успешно настроена
    </div>
    <?php
} else {
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
              integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
              crossorigin="anonymous">
        <title>Micros Telegram bot</title>
    </head>
    <body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-9 shadow-lg p-3 mb-5 bg-body rounded">
                <h2 class="text-center">Micros Telegram bot</h2>
                <form id="installationForm">
                    <h5>Параметры Коннектора</h5>
                    <div class="mb-3">
                        <label for="connectorname" class="form-label">Название Коннектора</label>
                        <input type="text" class="form-control" id="connectorname" required>
                    </div>
                    <div class="mb-3">
                        <label for="logofile" class="form-label">Лого Коннектора</label>
                        <input type="file" class="form-control" id="logofile" required>
                        <input type="hidden" id="base64format">
                    </div>
                    <h5>Параметры Telegram Bot</h5>
                    <div class="connector-request-block">
                        <div class="mb-3">
                            <label for="tokentelegrambot" class="form-label">Токен Telegram bot</label>
                            <input type="text" class="form-control" id="tokentelegrambot" required>
                        </div>
                        <div id="request-add-block">

                        </div>
                        <button class="btn btn-secondary mb-3" type="button" id="addButton">Добавить запрос</button>
                    </div>
                    <div class="alert alert-success text-center" id="appResultMessageSuccess" style="display: none"
                         role="alert">
                        Данные успешно сохранены
                    </div>
                    <div class="alert alert-danger text-center" id="appResultMessageError" style="display: none"
                         role="alert">
                        Проверьте правильность данных
                    </div>
                    <div class="d-flex justify-content-center">

                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="//api.bitrix24.com/api/v1/dev/"></script>
    <script src="installHandler.js?v=5"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
            crossorigin="anonymous"></script>

    </body>
    </html>
    <?php

    function LogS($result, $comment)
    {
        $html = '\-------' . $comment . "---------\n";
        $html .= print_r($result, true);
        $html .= "\n" . date("d.m.Y H:i:s") . "\n--------------------\n\n\n";
        $file = $_SERVER["DOCUMENT_ROOT"] . "/dev/sher/logS.txt";
        $old_data = file_get_contents($file);
        file_put_contents($file, $html . $old_data);
    }

    class TelegramAPI
    {
        private $token;
        private $apiUrl;

        public function __construct()
        {
            $this->token = trim(CRest::call("app.option.get", [])["result"]["token"]);
            $this->apiUrl = "https://api.telegram.org/bot$this->token/";
        }

        public function sendRequest($method, $data)
        {
            $data["parse_mode"] = "HTML";
            $url = $this->apiUrl . $method;
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response, true);
        }

        public function sendMessage($chatId, $message, $keyboard = null)
        {
            $data = ["chat_id" => $chatId, "text" => $message, "parse_mode" => "HTML"];

            if ($keyboard) {
                $data["reply_markup"] = json_encode($keyboard);
            }

            return $this->sendRequest("sendMessage", $data);
        }

        public function getUserProfilePhoto($userId)
        {
            $result = $this->sendRequest("getUserProfilePhotos", ["user_id" => $userId, "limit" => 1]);

            if (!empty($result["result"]["photos"])) {
                // Get the largest available photo
                $photos = $result["result"]["photos"][0]; // First photo set
                $fileId = end($photos)["file_id"]; // Get highest quality

                return $this->getFilePath($fileId);
            } else {
                return null; // No profile photo found
            }

        }

        public function editMessage($chatId, $messageId, $newText)
        {
            $data = [
                "chat_id" => $chatId,
                "message_id" => $messageId,
                "text" => $newText,
                "parse_mode" => "HTML"
            ];
            return $this->sendRequest("editMessageText", $data);
        }

        public function deleteMessage($chatId, $messageId)
        {
            $data = [
                "chat_id" => $chatId,
                "message_id" => $messageId
            ];
            return $this->sendRequest("deleteMessage", $data);
        }

        public function getProfileLink($username)
        {
            return "https://t.me/" . $username;
        }

        public function sendFile($method, $data, $caption = null)
        {
            if ($caption) {
                $data["caption"] = $caption;
            }
            return $this->sendRequest($method, $data);
        }

        public function getFilePath($fileId)
        {
            $response = $this->sendRequest("getFile", ["file_id" => $fileId]);
            return isset($response["result"]["file_path"])
                ? "https://api.telegram.org/file/bot{$this->token}/" . $response["result"]["file_path"]
                : null;
        }
    }

    class UserManager
    {
        private $entity = "telegramusers";

        public function loadUserData($chatId)
        {
            $getResult = CRest::call("entity.item.get", ["ENTITY" => $this->entity, "FILTER" => ["CODE" => $chatId]]);
            if (!empty($getResult["result"])) {
                return ["ID" => $getResult["result"][0]["ID"], "DATA" => json_decode($getResult["result"][0]["PREVIEW_TEXT"], true)];
            } else {
                return [];
            }
        }

        public function saveUserData($user)
        {

            $getResult = self::loadUserData($user["chat_id"]);

            if (!empty($getResult)) {
                CRest::call("entity.item.update", ["ENTITY" => $this->entity, "ID" => $getResult["ID"], "NAME" => $user["name"], "PREVIEW_TEXT" => json_encode($user)]);
            } else {
                CRest::call("entity.item.add", ["ENTITY" => $this->entity, "NAME" => $user["name"],"CODE"=>$user["chat_id"], "PREVIEW_TEXT" => json_encode($user)]);
            }
        }
    }

    class CRMConnector
    {
        public function sendToCRM($data, $method = "imconnector.send.messages")
        {

            $chatId = $data["chat_id"];
            $fileData = $data["fileData"];
            $user = $data["user"];

            $message = array(
                array(
                    //Массив описания пользователя
                    'user' => array(
                        'id' => $chatId,//ID пользователя во внешней системе *
                        'last_name' => $user["company"],//Фамилия
                        'name' => $user["name"],//Имя
                        'picture' => array(
                            'url' => $user["photo"]
                        ),
                        'url' => $user["link"],//Ссылка на профиль пользователя
                        'sex',//Пол. Допустимо male и female
                        'email', //email
                        'phone' => $user["phone"], //телефон
                        'skip_phone_validate' => 'Y', //В значении 'Y' позволяет не применять валидацию
                        //номера телефона пользователя. По умолчанию
                    ),
                    //Массив описания сообщения
                    'message' => array(
                        'id' => $data["message_id"], //ID сообщения во внешней системе.*
                        'date', //Время сообщения в формате timestamp *
                        'disable_crm' => 'Y',//отключить чат трекер (CRM трекер)
                        'text' => $data["text"], //Текст сообщения. Должен быть указан элемент text или files.
                        'files' => [$fileData],//Массив описаний файлов, где каждый файл описывается
                        //массивом, со ссылкой, которая доступна порталу
                    ),
                    //Массив описания чата
                    'chat' => array(
                        'id' => $chatId,//ID чата во внешней системе *
                        'name' => $user["name"], //Имя чата во внешней системе
                        'url' => $user["link"], //Ссылка на чат во внешней системе
                    ),
                ),
            );

            $dataConnector = CRest::call("app.option.get", []);
            $openlineID = $dataConnector["result"]["openlineID"];
            $connector = "micros_telegram_bot";
            $response = CRest::call($method, [
                "CONNECTOR" => $connector,
                "LINE" => $openlineID,
                "MESSAGES" => $message
            ]);

            if ($response["result"]["SUCCESS"] && isset($response["result"]["DATA"]["RESULT"][0]["session"])) {
                $sessionChatData = $response["result"]["DATA"]["RESULT"][0]["session"];
                if ($data["set_title"]) {
                    CRest::call('imconnector.chat.name.set', [
                        "CONNECTOR" => $connector,
                        "LINE" => $openlineID,
                        "CHAT_ID" => $sessionChatData["CHAT_ID"],
                        "NAME" => $user["company"] . " - " . $user["name"]
                    ]);
                }
                $dialogResult = CRest::call('imopenlines.dialog.get', ["DIALOG_ID" => "chat" . $sessionChatData["CHAT_ID"]]);

                if ($chatData = $dialogResult["result"]) {

                    $str = $chatData["entity_data_1"];
                    $parts = explode("|", $str);
                    $key = array_search("LEAD", $parts);
                    if ($key !== false && isset($parts[$key + 1])) {
                        $leadID = $parts[$key + 1];
                        CRest::call('crm.lead.update', ["id" => $leadID, "fields" => ["COMPANY_TITLE" => $user["company"]]]);
                    }
                }
            } else {
                return $response;
            }
        }
    }

    class TelegramBot
    {
        private $api;
        private $userManager;
        private $crmConnector;

        public function __construct()
        {
            $this->api = new TelegramAPI();
            $this->userManager = new UserManager();
            $this->crmConnector = new CRMConnector();
        }

        public function handleRequest()
        {
            $data = file_get_contents("php://input");
            $update = json_decode($data, true);

            if (isset($update["message"])) {
                $message = $update["message"];
                $date = $message["date"];
                $username = $message["chat"]["username"];
                $contact = $message["contact"]["phone_number"] ?? null;
                $method = false;
            } elseif ($update["edited_message"]) {
                $message = $update["edited_message"];
                $date = $message["edit_date"];
                $method = "imconnector.update.messages";
            }
            $chatId = $message["chat"]["id"];
            $text = trim($message["text"]);

            $fileData = null;

            if (isset($message["photo"])) {
                $fileId = end($message["photo"])["file_id"];
                $text = $message["caption"];
                $fileData = ["url" => $this->api->getFilePath($fileId)];
            }

            if (isset($message["document"])) {
                $fileId = $message["document"]["file_id"];
                $fileData = ["url" => $this->api->getFilePath($fileId)];
                $text = $message["caption"];
            }
            if($userData =$this->userManager->loadUserData($chatId)){
                $userData = $userData["DATA"];
            }
            $data = [
                "user" => $userData,
                "chat_id" => $chatId,
                "message_id" => $message["message_id"],
                "text" => $text,
                "contact" => $contact,
                "fileData" => $fileData,
                "username" => $username??"Гость",
                "date" => $date
            ];

            if ($method) {
                $this->crmConnector->sendToCRM($data, $method);
            } else {
                $this->processMessage($data);
            }
        }

        private function processMessage($data)
        {
            $user = $data["user"];
            $chatId = $data["chat_id"];
            $text = $data["text"];
            $contact = $data["contact"];
            $username = $data["username"];
            $dataConnector = CRest::call("app.option.get", []);
            if ($text === "/start") {
                if($user["step"]=="done"){
                    $userPhoto = $this->api->getUserProfilePhoto($chatId);
                    $userProfileUrl = $this->api->getProfileLink($username);
                    $user["photo"] = $userPhoto;
                    $user["link"] = $userProfileUrl;
                    $data["user"] = $user;
                    $this->sendToCRM($data); 
 
                }else{
                    $greeting = $dataConnector["result"]["requestlist"][0];
                    $this->api->sendMessage($chatId, $greeting);
                    $user = ["step" => "waiting_for_name"];
                    $user["chat_id"] = $chatId;
                    $user["name"] = $username;
                }
                $this->userManager->saveUserData($user);
            } else {
                switch ($user["step"]) {
                    case "waiting_for_name":
                        $user["name"] = $text;
                        $user["step"] = "waiting_for_company";
                        $company = $dataConnector["result"]["requestlist"][1];
                        $this->api->sendMessage($chatId, $company);
                        $this->userManager->saveUserData($user);
                        break;

                    case "waiting_for_company":
                        $user["company"] = $text;
                        $user["step"] = "waiting_for_phone";
                        $phoneNumber = $dataConnector["result"]["requestlist"][2];
                        $this->api->sendMessage($chatId, $phoneNumber, [
                            "keyboard" => [[["text" => "Отправить номер", "request_contact" => true]]],
                            "resize_keyboard" => true,
                            "one_time_keyboard" => true
                        ]);
                        $this->userManager->saveUserData($user);
                        break;

                    case "waiting_for_phone": // Получаем номер телефона
                        if ($contact) {
                            $user["phone"] = $contact;
                            $user["step"] = "done";

                            $resOpenline = CRest::call("imopenlines.config.get", ["CONFIG_ID"=>$dataConnector["result"]["openlineID"]]);

                            $itsOK = $dataConnector["result"]["requestlist"][3];

                            //Проверяем рабочего времени, если Да тогда отправляем последний сохраненный запрос из настройки приложения
                            if($openLineData = $resOpenline["result"]){
                                if($this->isWorkTime($openLineData) && $openLineData["WORKTIME_ENABLE"]=="Y"){
                                    $this->api->sendMessage($chatId, $itsOK, ["remove_keyboard" => true]);
                                }
                            }
                            $userPhoto = $this->api->getUserProfilePhoto($chatId);
                            $userProfileUrl = $this->api->getProfileLink($username);
                            $user["photo"] = $userPhoto;
                            $user["link"] = $userProfileUrl;

                            $this->sendConversationToCRM($chatId, $user);
                            $this->userManager->saveUserData($user);
                        } else {
                            $this->api->sendMessage($chatId, "Пожалуйста, отправьте номер через кнопку.");
                        }
                        break;
                    default:
                        $this->crmConnector->sendToCRM($data);
                }
            }
        }

        private function sendConversationToCRM($chatId, $user)
        {

            $crmMessage = "Имя: " . ($user["name"] ?? "Неизвестно") . "\n";
            $crmMessage .= "Компания: " . ($user["company"] ?? "Неизвестно") . "\n";
            $crmMessage .= "Телефон: " . ($user["phone"] ?? "Неизвестно") . "\n";


            $crmData = [
                "user" => $user,
                "chat_id" => $chatId,
                "text" => $crmMessage,
                "set_title" => true
            ];

            $this->crmConnector->sendToCRM($crmData);
        }


        public function handleCrmRequest()
        {
            if ($_REQUEST["data"]["CONNECTOR"] == "micros_telegram_bot") {

                $openlineID = $_REQUEST["data"]["LINE"];
                $messageData = $_REQUEST["data"]["MESSAGES"][0];
                $chat = $messageData["chat"];
                $message = $messageData["message"];

                if ($message["text"]) {

                    if ($_REQUEST["event"] == "ONIMCONNECTORMESSAGEUPDATE") {

                        $this->api->editMessage($chat["id"], $message["id"][0], $this->formatTextFromIM($message["text"]));

                    } elseif ($_REQUEST["event"] == "ONIMCONNECTORMESSAGEADD") {
                        $sendMessage = $this->api->sendMessage($chat["id"], $this->formatTextFromIM($message["text"]));
                    }

                } elseif ($message["files"]) {
                    foreach ($message["files"] as $file) {

                        if ($file["type"] == "image") {
                            $method = "sendPhoto";
                            $fileType = "photo";
                        } else {
                            $method = "sendDocument";
                            $fileType = "document";
                        }
                        $this->api->sendFile($method, ["chat_id" => $chat["id"], $fileType => $file["link"]]);
                    }
                }
                if ($_REQUEST["event"] == "ONIMCONNECTORMESSAGEDELETE") {
                    $this->api->deleteMessage($chat["id"], $message["id"]);
                }


                CRest::call("imconnector.send.status.delivery", [
                    "CONNECTOR" => "micros_telegram_bot",
                    "LINE" => $openlineID,
                    "MESSAGES" => [
                        [
                            "im" => ["chat_id" => $messageData["chat"]["id"], "message_id" => $messageData["im"]["message_id"]],
                            "message" => ["id" => $sendMessage["result"]["message_id"]],
                            "chat" => ["id" => $sendMessage["result"]["chat"]["id"]
                            ]
                        ]
                    ]
                ]);
            }
        }
       private function isWorkTime($config) {
            $now = new DateTime();
            $hour = (int) $now->format('G'); // Current hour (0-23)
            $dayOfWeek = $now->format('D'); // Current day (Mon-Sun)

            // Check if today is a day off
            if (in_array(strtoupper(substr($dayOfWeek, 0, 2)), $config['WORKTIME_DAYOFF'])) {
                return false;
            }

            // Check if current time is within work hours
            return ($hour >= $config['WORKTIME_FROM'] && $hour < $config['WORKTIME_TO']);
        }
        public function formatTextFromIM($text)
        {

            require_once __DIR__ . "/JBBCode/Parser.php";

            $parser = new JBBCode\Parser();
            $parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());
            $text = str_replace("[br]", "\n", $text);
            $parser->parse($text);

            return $parser->getAsHtml();

        }
    }


    $bot = new TelegramBot();
    $bot->handleRequest();
    $bot->handleCrmRequest();

} ?>



