<?php
// setting the bot token from @BotFather
$GLOBALS["TG_BOT_TOKEN"] = getenv("TG_BOT_TOKEN");

// the message that should be displayed,
// when the bot is started
$GLOBALS["START_MESSAGE"] = <<<EOM
Hi.!

I'm Subtitle SearchEr Bot.
I can provide movie / series subtitles.

Type the movie / series name,
and let me try to do the megick..!

Subscribe ℹ️ @SpEcHlDe if you ❤️ using this bot!
EOM;

$GLOBALS["CHECKING_MESSAGE"] = "🤔";

$GLOBALS["SAPI_BASE_URL"] = getenv("SAPI_BASE_URL");

$GLOBALS["MESG_DETIDE"] = <<<EOM
please select your required subtitle
EOM;

/**
 * a wraper function to call the search api,
 * and return a reply_markup containing Telegram Buttons
 */
function search_srt_a($s, $p) {
    // set the search URL with the search query
    $search_url = $GLOBALS["SAPI_BASE_URL"] . "/search/" . urlencode($s) . "/" . $p . "";

    // get the responses from the API
    $search_response = json_decode(
        file_get_contents(
            $search_url
        ),
        true
    );

    // initialize an empty array
    // which would store the final "reply_markup"
    $reply_markup_inline_keyboard_arrey = array();

    foreach ($search_response["r"] as $key => $value) {
        // this should contain the CAPTION that can be displayed on the button
        $message_caption = $value["SIQ"];
        // the file size of the SRT file
        $file_size = $value["ISF"];
        // the unique ID of the SRT file, to uniquely identify it
        $sub_id = $value["DMCA_ID"];
        // direct download link of the file,
        // can be empty (at times)
        $direct_download_link = $value["DLL"];

        $reply_markup_inline_keyboard_arrey[] = array(
            array(
                "text" => $message_caption,
                "callback_data" => "" . "dl_" . $sub_id . ""
            )      
        );
    }

    $reply_markup = json_encode(array(
        "inline_keyboard" => $reply_markup_inline_keyboard_arrey
    ));
    return $reply_markup; 
}

/**
 * get subtitle file, from subtitle_id
 */
function get_sub_i($sub_id, $user_id) {
    $sub_get_url = $GLOBALS["SAPI_BASE_URL"] . "/get/" . $sub_id . "/";

    // get the responses from the API
    $subg_response = json_decode(
        file_get_contents(
            $sub_get_url
        ),
        true
    );

    // get the REQuired parameters from the API
    $sub_download_link = $GLOBALS["SAPI_BASE_URL"] . $subg_response["DL_LINK"];
    $sub_language = $subg_response["DL_LANGUAGE"];
    $sub_file_name = $subg_response["DL_SUB_NAME"];

    // also, return the LEGal DISclaimer,
    // provided by the API, JIC..!
    $sub_legal_disclaimer = $subg_response["LEGAL"];

    // download the file
    file_put_contents(
        $sub_file_name,
        file_get_contents(
            $sub_download_link
        )
    );

    $tg_message_caption = "";
    if ($sub_language != "") {
        $tg_message_caption .= "<b>Language</b>: " . $sub_language . "\n";
    }
    $tg_message_caption .= $sub_legal_disclaimer;
    
    return array(
        "chat_id" => $user_id,
        "document" => $sub_file_name,
        "caption" => $tg_message_caption,
        "parse_mode" => "HTML",
        "disable_notification" => True
    );
}

// import Telegram Bot API libraries
require_once __DIR__ . "/../vendor/autoload.php";
