<?php
include 'crest.php';
$result = CRest::installApp();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
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
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary">Установить</button>
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
