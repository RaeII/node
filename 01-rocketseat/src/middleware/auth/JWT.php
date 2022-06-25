<?php
namespace Middleware\Auth;

class JWT
{
    private $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    private $iss = 'apivoos';
    private $sub = NULL;
    private $aud = NULL;
    private $aud_company;

    public function __construct($sub, $aud, $aud_company) {
        # ext = external
        $this->sub = $sub;
        $this->aud = $aud;
        $this->aud_company = $aud_company;
    }

    public function getId() {
        return $this->aud;
    }

    public function getCompanyId() {
        return $this->aud_company;
    }

    public function generate($iat = NULL, $exp = NULL)
    {
        # checks if the $iat and $exp where given during the call
        # if not, it generates based in the current strtotime
        if($iat == NULL or $exp == NULL)
        {
            $iat = strtotime('now');
            $exp = strtotime("+1 day", strtotime('now'));
        }

        # default JWT token generator
        $funcHeader = json_encode($this->header); # function header, from self header
        $funcHeader = base64_encode($funcHeader);

        $payload = [
            'iss' => $this->iss,
            'sub' => $this->sub,
            'aud' => $this->aud,
            'iat' => strval($iat), # str to avoid illegal string offsets
            'exp' => strval($exp),  # str to avoid illegal string offsets
            'aud_company' => $this->aud_company
        ];

        $payload = json_encode($payload);
        $payload = base64_encode($payload);
        
        # key to encode the JWT
        $key = base64_encode($this->iss.$iat).strtotime("+1 day", $iat);

        $signature = hash_hmac('sha256',"$funcHeader.$payload",$key, true);
        $signature = base64_encode($signature);
        
        # cleans the JWT and make it valid
        return str_replace(['+', '/', '='], ['-', '_', ''], $funcHeader.".".$payload.".".$signature);
    }

    public static function decodeAndValidate($tokenOriginal) {
        if($tokenOriginal == null) throw new \Exception(getErrorMessage('missingJWTToken'));

        $tokenFormated = explode('.', $tokenOriginal);

        if(count($tokenFormated) != 3) throw new \Exception(getErrorMessage('wrongJWTSignature'));

        # decodes the JWT
        $tokenDecoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenFormated[1]))), true);
        /*
            Validation checks
            generate a token and compare it to the current given one.
        */
        $tokenNew = new JWT($tokenDecoded['sub'], $tokenDecoded['aud'], $tokenDecoded['aud_company']);
        $another = $tokenNew->generate($tokenDecoded['iat'], $tokenDecoded['exp']);
        if(!($another === $tokenOriginal
        /* 
            Dates comparisions
            check if was generated after the current date.
        */
        and $tokenDecoded['iat'] <= strtotime('now')
        # check if its still valid
        and $tokenDecoded['exp'] >= strtotime('now')
        )) throw new \Exception(getErrorMessage('wrongJWTSignature'));

        return $tokenNew;
    }

}

