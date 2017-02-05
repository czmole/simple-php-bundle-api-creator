<?php
	require_once('vendor/autoload.php');
	require_once('resources/helpers.php');

	date_default_timezone_set('Europe/Bucharest');

	use Valitron\Validator as Validator;
	use \Firebase\JWT\JWT as JWT;
	use Intervention\Image\ImageManagerStatic as Image;


	$app = new \OnePHP\App();
	$dotenv = new Dotenv\Dotenv(__DIR__);
	$dotenv->overload();

	Validator::langDir(__DIR__ . getenv("APP_VALIDATOR_LANG_PATH")); // always set langDir before lang.
	Validator::lang('ro');

	$global['ver'] = getenv('APP_VER');
	$pdo = new PDO("mysql:dbname=". getenv("DB_NAME"), getenv("DB_USER"), getenv("DB_PASS"));
	$global['db'] = new FluentPDO($pdo);

	/**************************************************************************************************************
	Global data - will be outputted on each call/reasponse
	***************************************************************************************************************/
	// $data['name'] = getenv('APP_NAME');
	// $data['ver'] = $global['ver'];
	// $data['time'] = time();
	// $data['dt'] = date('Y-m-d H:i:s');
	$data = array();



	/**************************************************************************************************************
	Create token for speciffic user
	***************************************************************************************************************/
	function tokenize($userDetails) {
		// $tokenId    = base64_encode(mcrypt_create_iv(32)); // this is recommended to be used, in case it can not be used leave the line below
		$tokenId    = base64_encode(devurandom_rand());
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;  //Adding 10 seconds
        $expire     = $notBefore + 864000; // Adding 10 days in seconds
        $serverName = getenv("APP_URL"); /// set your domain name

        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => [
					'id' => $userDetails['id']
				]
        ];

 		try {
 			$jwt = JWT::encode(
                $data,
                getenv("APP_KEY"),
                getenv("APP_ALG")
            );
 		} catch (Exception $e) {
 			return array( "success" => false, "error" => $e->getMessage() );
 		}

		return array( "success" => true, "jwt" => $jwt );
	}



	/**************************************************************************************************************
	Securing routes requiring authentication
	***************************************************************************************************************/
	function secure($app, $global) {
		$data['auth'] = false;

		if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
			echo $app->JsonResponse(array('errors' => ['token' => 'Unauthorized']), 401);
			exit();
		}

		$token = explode(" ",$_SERVER["HTTP_AUTHORIZATION"]);
		if (count($token) < 2) {
			echo $app->JsonResponse(array(), 401);
			exit();
		}

		$token = $token[1];

		try {
			$jwt = JWT::decode($token, getenv("APP_KEY"), array( getenv("APP_ALG") ) );

			$db = $global["db"];
			$user = $db->from('users')
				            ->where('id', $jwt->data->id)
				            ->fetch();

			unset($user["password"]);

			$user = prepare_array($user);

			return $user;
		} catch (Exception $e) {
			echo $app->JsonResponse(array('errors' => ['token' => 'Unauthorized - '. $e->getMessage()]), 401);
			exit();
		}
	}




	/**************************************************************************************************************
	General Route Catcher for 404 errors
	***************************************************************************************************************/
	$app->respond( function() use ( $app, $global, $data ){
		$data['errors'] = array(
				'Route not found.'
			);
		return $app->JsonResponse($data, 404);
    });



	/**************************************************************************************************************
	Index
	***************************************************************************************************************/
	$app->get('/', function() use ( $app, $global, $data ){
		header("Location: https://google.com");
		die();
    });




	/**************************************************************************************************************
	Authenticaion
	Providing details about the API
	***************************************************************************************************************/
	$app->post('/login',function() use ( $app, $global, $data ){
		$inputs = $_POST;
		$inputs["password"] = isset($inputs["password"])? $inputs["password"] : NULL;

		$v = new Validator($inputs);
		$v->rule('required', ['email', 'password']);
		$v->rule('email', 'email');

		if ( !$v->validate() ) {
		    $data['errors'] = parseErrors($v->errors());
		    return $app->JsonResponse($data, 403);
		}

		$db = $global["db"];
		$users = $db->from('users')
			            ->where('email', $inputs["email"])
			            ->fetch();

		// do here the required checks if the password is matching

		if ( !count($users) ) {
		    $data['errors'] = parseErrors(array(
							    	'users' => 'Wrong username/email.'
							    ));
		    return $app->JsonResponse($data, 403);
		}

		$theUser = $users;
		unset($theUser["password"]);

		$token = tokenize($theUser);
		if (!$token["success"]) {
		    $data['errors'] = array(
		    	'users' => $token["error"]
		    );
		    return $app->JsonResponse($data, 401);
		}

		unset($users["password"]);

		$data["user"] = $users;
		$data["token"] = $token["jwt"];

		return $app->JsonResponse($data);
	});


	/**************************************************************************************************************
	Provide details about own and a given profile
	***************************************************************************************************************/
	$app->get('/profile',function() use ( $app, $global, $data ){
		$session = secure($app, $global);
		$db = $global["db"];

		$inputs = $_GET;

		if (isset($inputs["user_id"])) {
			$v = new Validator($inputs);
			$v->rule('integer', 'user_id');

			if ( !$v->validate() ) {
				$data['errors'] = parseErrors($v->errors());
				return $app->JsonResponse($data, 403);
			}
		}

		if (!isset($inputs["user_id"]) || (isset($inputs["user_id"]) && $inputs["user_id"] == $session["id"])) {
			$response = $session;

		} else {
			$db = $global["db"];
			$profile = $db->from('users')
							->where('id', $inputs['user_id'])
							->fetch();

			if ( !$profile ) {
				$data['errors'] = array (
					'context' => 'user_id',
					'message' => 'User not found.'
				);
				return $app->JsonResponse($data, 403);
			} else {
				$response = $profile;
			}
		}


		return $app->JsonResponse($response);
	});



	/**************************************************************************************************************
	Update own profile
	***************************************************************************************************************/
	$app->put('/profile',function() use ( $app, $global, $data ){
		$session = secure($app, $global);
		$db = $global["db"];
		parse_str(file_get_contents("php://input"), $inputs);

		$v = new Validator($inputs);
		$v->rule('required', array(
			'email',
			'firstname',
			'lastname',
		));
		$v->rule('email', 'email');
		$v->rule('optional', 'password');
		$v->rule('lengthMin', 'password', 8);

		if ( !$v->validate() ) {
			$data['errors'] = parseErrors($v->errors());
			return $app->JsonResponse($data, 403);
		}

		// do what it requires to update the user
		return $app->JsonResponse($response);
	});


	// RUN THE APP
	$app->listen();
?>
