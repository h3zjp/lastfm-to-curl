<?php

	# エラー表示
	ini_set( 'display_errors', 1 );

	# フォルダパス
	$folder = 'ここにフルパス(/で終わる)を記述';

	# ライブラリ読み込み
	require $folder . 'vendor/autoload.php';
	use Abraham\TwitterOAuth\TwitterOAuth;

	# 投稿の有無 (投稿: true)
	$posting = true;
	$posting_twtr    = false;
	$posting_msky    = false;
	$posting_nostr   = false;
	$posting_bsky    = false;
	$posting_concrnt = false;

	# 旧Twitter API Key
	$twtr_apikey      = 'API Key (Consumer Key)';
	$twtr_apisecret   = 'API Secret (Consumer Secret)';
	$twtr_accesstoken = 'OAuth Access Token';
	$twtr_tokensecret = 'OAuth Access Token Secret';

	# 設定
	$apikey   = 'Last.fm API Key';
	$username = 'Last.fm ユーザー名';

	# API Endpoint
	$api = 'https://ws.audioscrobbler.com/2.0/?method=user.getRecentTracks&user=' . $username . '&api_key=' . $apikey . '&limit=1&format=json';

	# 取得
	$ch = curl_init($api);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch, CURLOPT_USERAGENT, 'Lastfm_API');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$json = mb_convert_encoding($response, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$result = json_decode($json, true);

	# ファイル読み込み
	$filename = $folder . 'data.csv';
	$filer = fopen($filename, 'r');
	$rcsv = fgetcsv($filer);
	$playing_data[0] = $rcsv[1];
	$playing_data[1] = $rcsv[2];
	$playing_data[2] = $rcsv[3];
	$playing_data[3] = $rcsv[4];
	$playing_data[4] = $rcsv[5];
	$playing_data[5] = $rcsv[6];
	fclose($filer);

	# アーティスト名
	$now_playing[0] = $result['recenttracks']['track'][0]['artist']['#text'];
	# 曲名
	$now_playing[1] = $result['recenttracks']['track'][0]['name'];
	# ジャケット画像
	$now_playing[2] = $result['recenttracks']['track'][0]['image'][3]['#text'];
	# URL
	$now_playing[3] = $result['recenttracks']['track'][0]['url'];
	# NowPlaying かどうか
	if ($result['recenttracks']['track'][0]['@attr']['nowplaying'] == true) {

		$now_playing[4] = 1;

	 } else {

		$now_playing[4] = 0;

	}

	# 再生回数取得
	$api2 = 'https://ws.audioscrobbler.com/2.0/?method=track.getInfo&user=' . $username . '&api_key=' . $apikey . '&artist=' . $now_playing[0] . '&track=' . $now_playing[1] . '&format=json';
	$api2 = str_replace(" ","%20",$api2);
	$ch = curl_init($api2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($ch, CURLOPT_USERAGENT, 'Lastfm_API');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response2 = curl_exec($ch);
	curl_close($ch);
	$json2 = mb_convert_encoding($response2, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$result2 = json_decode($json2, true);

	if (isset($result2['track']['userplaycount'])) {

		$now_playing[5] = $result2['track']['userplaycount'];

	} else {

		$now_playing[5] = '--';

	}

	# ファイル書き込み
	if (!empty($result['recenttracks'])) {
    	$filew = fopen($filename, 'w');
    	$req_date = date("Y-m-d H:i T", $_SERVER['REQUEST_TIME']);
    	$wcsv = array($req_date, $now_playing[0], $now_playing[1], $now_playing[2], $now_playing[3], $now_playing[4], $now_playing[5]);
    	fputcsv($filew, $wcsv);
    	fclose($filew);
	}

	# 投稿データ作成
	if ($now_playing[4] == 1) {

		if (($playing_data[3] != $now_playing[3]) && isset($now_playing[3])) {

			$post_arr = array();
			$post_arr[] = "#なうぷれ♪ (";
			$post_arr[] = $now_playing[5];
			$post_arr[] = "回再生)\n";
			$post_arr[] = $now_playing[1];
			$post_arr[] = " / ";
			$post_arr[] = $now_playing[0];
			$post_arr[] = "\n#nowplaying";

			$post_data = implode($post_arr);

			$post_arr[] = "\n";
			$post_arr[] = $now_playing[2];

			$post_data2 = implode($post_arr);

		} else {

			$post_data == "";

		}

	} else {

		$post_data == "";

	}

	# 投稿
	if ($posting == true) {

		# 旧Twitter
		if ($posting_twtr == true) {

			if (!empty($post_data)) {

				$twtr_connection = new TwitterOAuth($twtr_apikey, $twtr_apisecret, $twtr_accesstoken, $twtr_tokensecret);
				$twtr_connection->setApiVersion('2');
				$twtr_result = $twtr_connection->post('tweets', ['text' => $post_data], ['jsonPayload' => true]);

			}

		}

		# Misskey
		# misskey.io の場合、サーバーとアクセストークンを設定するだけで動きます
		if ($posting_msky == true) {

			if (!empty($post_data)) {

				$data = [
					'i' => 'Misskey Access Token',
					'text' => $post_data,
					'visibility' => 'public'
				];

				$json_data = json_encode($data);

				$put_url = 'https://misskey.io/api/notes/create';

				$ch = curl_init($put_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_exec($ch);
				curl_close($ch);

			}

		}

		# Nostr
		# https://github.com/mattn/algia を使う前提のサンプル
		if ($posting_nostr == true) {

			if (!empty($post_data2)) {

				$data = [
					'note' => $post_data2
				];

				$json_data = json_encode($data);

				$put_url = 'http://127.0.0.1:10000/post';

				$ch = curl_init($put_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_exec($ch);
				curl_close($ch);

			}

		}

		# Bluesky
		# https://github.com/mattn/bsky を使う前提のサンプル
		if ($posting_bsky == true) {

			if (!empty($post_data)) {

				$data = [
					'note' => $post_data
				];

				$json_data = json_encode($data);

				$put_url = 'http://127.0.0.1:10010/post';

				$ch = curl_init($put_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_exec($ch);
				curl_close($ch);

			}

		}

		# Concurrent
		# https://github.com/rassi0429/concurrent-webhook を使う前提のサンプル
		if ($posting_concrnt == true) {

			if (!empty($post_data)) {

				$data = [
					'text' => $post_data
				];

				$json_data = json_encode($data);

				$put_url = 'http://127.0.0.1:10020/post';

				$ch = curl_init($put_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_exec($ch);
				curl_close($ch);

			}

		}

	}

?>
