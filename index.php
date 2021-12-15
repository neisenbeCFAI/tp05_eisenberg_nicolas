<?php
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Slim\Factory\AppFactory;
    use \Firebase\JWT\JWT;
    
    require __DIR__ . '/../vendor/autoload.php';

    function addHeaders(Response $response): Response
    {
        $response = $response
        ->withHeader("Content-Type", "application/json")
        ->withHeader("Access-Control-Allow-Origin", ("*"))
        ->withHeader("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Accept, Origin, Authorization")
        ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
        ->withHeader("Access-Control-Expose-Headers", "Authorization");

        return $response;
    }

    const JWT_SECRET = "neisenbeJWT";

    $options = [
        'attribute' => 'token',
        'header' => 'Authorization',
        'regexp' => "/Bearer\s+(.*)$/i",
        'secure' => false,
        'algorithm' => ['HS256'],
        'secret' => JWT_SECRET,
        'path' => ['/api'],
        'ignore' => ["/api/login", "/api/auth"],
        "error" => function ($response, $arguments) {
            $data = array("ERROR" => "Login", "Error" => "JWT invalid");
            $response = $response->withStatus(401);
            return $response->withHeader("Content-Type", "application/json")->getBody()->write(json_encode($data));
        }
    ];

    function createJWT(Response $response): Response
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 600;
        $payload = array(
            'iat' => $issuedAt,
            'exp' => $expirationTime
        );

        $token_jwt = JWT::encode($payload, JWT_SECRET, "HS256");

        $response = $response->withHeader("Authorization", "Bearer {$token_jwt}");

        return $response;
    }
    
    
    $app = AppFactory::create();
    
    $app->get("/api/v1/auth/{login}", function(Request $request, Response $response, $args)
    {
        $login = $args['login'];

        if($login)
        {
            $data = array('login' => $login);
            $response = addHeaders($response, $request->getHeader('Origin'));
            $response = createJWT($response);
            $response->getBody()->write(json_encode($data));
        }
        else
        {
            $response = $response->withStatus(401);
        }

        return $response;
    });

    $app->post("/api/v1/login", function(Request $request, Response $response, $args)
    {
        
        $validLogin = true;
        $body = $request->getParsedBody();
        $usr = $body['username'] ?? "";
        $pwd = $body['password'] ?? "";

        if(!preg_match("/[a-zA-Z0-9]{1,20}/", $usr))
            $validLogin = false;

        /* To delete once a real auth exists */
        if($usr != "root")
            $validLogin = false;
        if($pwd != "fakepwd")
            $validLogin = false;
        /* To delete once a real auth exists */


        if($validLogin)
        {
            $response = addHeaders($response);
            $response = createJWT($response);
            $data = array('login' => $usr, 'password' => $pwd);
            $response->getBody()->write(json_encode($data));
        }
        else
        {
            $response = $response->withStatus(401);
        }

        return $response;
    });

    $app->post("/api/v1/customer", function(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $firstName = $body['firstName'] ?? "";
        $lastName = $body['lastName'] ?? "";
        $address = $body['address'] ?? "";
        $city = $body['city'] ?? "";
        $cp = $body['cp'] ?? "";
        $country = $body['country'] ?? "";
        $telephone = $body['telephone'] ?? "";
        $email = $body['email'] ?? "";
        $gender = $body['gender'] ?? "";
        $username = $body['username'] ?? "";
        $password = $body['password'] ?? "";

        $response->getBody()->write(json_encode($body));
    });

    $app->add(new Tuupola\Middleware\JwtAuthentication($options));

    $app->run();
?>