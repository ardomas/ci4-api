<?php

namespace App\Libraries;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JWTLib
{
    private $key = "your_secret_key"; // Ganti dengan kunci rahasia kamu

    public function __construct(){
        $this->key = getenv('JWT.secret.key');
    }

    public function encode($data)
    {

        $payload = [
            'iss' => $data['iss'],      // iss => server token generator    - (issuer)
            'aud' => $data['aud'],      // aud => server token reciever     - (audience)
            // 'exe' => $data['exe'],
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,     // Token kedaluwarsa dalam 1 jam
            'data' => [
                'aid' => session_id(),  // session in this server
                'wid' => $data['wid'],  // session from requester server
                'uip' => $data['uip'],  // remote user
            ],
        ];

        // Encode JWT
        $encode_data = JWT::encode($payload, $this->key, 'HS256');

        // return encoded data;
        return $encode_data;

    }

    public function decode($jwt)
    {
        try {
            return JWT::decode($jwt, new Key($this->key, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}

?>