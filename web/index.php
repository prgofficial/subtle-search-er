<?php
require_once __DIR__ . "/config.php";
use kyle2142\PHPBot;

// set the bot TOKEN
$bot_id = $GLOBALS["TG_BOT_TOKEN"];
// initialize the Telegram Bot Object
$bot = new PHPBot($bot_id);
// get the Telegram webhook payload
$content = file_get_contents("php://input");
// JSONify the received payload
$update = json_decode($content, true);

// triggers to be handled when a message is received
if (isset($update["message"])) {
    $message_id = $update["message"]["message_id"];
    $chat_id = $update["message"]["chat"]["id"];
    $chat_type = $update["message"]["chat"]["type"];

    if (
        ($chat_type == "group") ||
        ($chat_type == "supergroup")
    ) {
        try {
            $bot->api->leaveChat(array(
                "chat_id" => $chat_id
            ));
        }
        catch (Exception $e) {
        }
        // return something to
        // pre-emptively exit from the script
        return FALSE;
    }

    // if we receive a message in PM
    if (isset($update["message"]["text"])) {
        $message_text = $update["message"]["text"];

        if (strpos($message_text, "/") !== FALSE) {
            $bot->api->sendMessage(array(
                "chat_id" => $chat_id,
                "text" => $GLOBALS["START_MESSAGE"],
                "parse_mode" => "HTML",
                "disable_notification" => True,
                "reply_to_message_id" => $message_id
            ));
        }

        else {
            // send a status message to edit the response,
            // as required
            $status_message = $bot->api->sendMessage(array(
                "chat_id" => $chat_id,
                "text" => $GLOBALS["CHECKING_MESSAGE"],
                "parse_mode" => "HTML",
                "disable_notification" => True,
                "reply_to_message_id" => $message_id
            ));

            // search subtitles using the API
            $reply_markup = search_srt_a(
                $message_text,
                1
            );

            // edit the previously sent message,
            // with the buttons
            $bot->api->editMessageText(array(
                "chat_id" => $chat_id,
                "message_id" => $status_message->message_id,
                "text" => $GLOBALS["MESG_DETIDE"],
                "parse_mode" => "HTML",
                "disable_web_page_preview" => True,
                "reply_markup" => $reply_markup
            ));
        }
    }
}


// triggers to be handled when a message is received,
// from a channel :\
if (isset($update["channel_post"])) {
    try {
        $bot->api->leaveChat(array(
            "chat_id" => $update["channel_post"]["chat"]["id"]
        ));
    }
    catch (Exception $e) {
    }
}


if (isset($update["callback_query"])) {
    $callback_query = $update["callback_query"];
    $id = $callback_query["id"];
    $message_from_user = $callback_query["from"];
    $chat_id = $callback_query["from"]["id"];
    $message = $callback_query["message"];
    $cb_data = $callback_query["data"];
    $message_id = $message["message_id"];

    // NOTE: You should always answer,
    // but we want different conditionals to
    // be able to answer to differnetly
    // (and we can only answer once),
    // so we don't always answer here.
    $bot->api->answerCallbackQuery(array(
        "callback_query_id" => $id
    ));
    // 
    // edit the previously sent message,
    // to avoid the repeated clicking of buttons
    $bot->api->editMessageText(array(
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $GLOBALS["CHECKING_MESSAGE"],
        "parse_mode" => "HTML",
        "disable_web_page_preview" => True
    ));

    if (strpos($cb_data, "dl_") !== FALSE) {
        // extract subtitle ID from the callback
        $sub_id = explode("_", $cb_data)[1];

        // get the subtitle to send
        $sub_doc_params = get_sub_i($sub_id, $chat_id);
        $sub_doc_params["reply_to_message_id"] = $message_id;

        // call the API, to send the DOCument
        $bot->api->sendDocument($sub_doc_params);

        // delete the SENT document
        @unlink($sub_doc_params["document"]);

        $bot->api->deleteMessage(array(
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ));
    }

    else {
        $bot->api->deleteMessage(array(
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ));
    }
}
