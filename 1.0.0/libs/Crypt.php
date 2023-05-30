<?php
declare(strict_types=1);
namespace Lay\libs;
/**
 * Password Encrypt Class for basic hashing
*/
final class Crypt {
    public ?string $password;
    function __construct(?string $password=null){
        if($password) return $this->password = $this->encrypt($password);
        return null;
    }
    //hashed values
    public function encrypt(string $password){
        return hash('sha512', "jnaOsk-kanjaS-cAs626-20Ica2-s06P6a-306W2a" . $password . "ashRjh-asDjhs-vjhSja-svHjas-hjhdAv-512Aiu");
    }

    /**
     * Encrypts and Decrypts
     *
     * @param string|null $string value to encrypt
     * @param bool $encrypt true [default]
     * @return string|null
     */
    public function toggleCrypt(?string $string, bool $encrypt = true): ?string {
        if($string == null) return null;
        $layer_1 = '@91_$!9u(2&y=uy+**43|\ur`y`3ut2%%iu#4#3(oo[u{3{4y7367622556';
        $layer = $this->encrypt("soft-salted-prefix-bini-name-included-to-avoid-brute-force-ukpato-evboehia-okogbo" .
            $layer_1 . "soft-salted-suffix-you-should-expect-giegbefumwen-maybe-ehose-nohaso");

        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha512', $layer);
        $iv = substr( hash( 'sha512', $layer_1 ), 0, 16 );

        if($encrypt)
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        else
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);

        if($output == false) $output = null;

        return $output;
    }

    public function csrf_gen(string $user_data) : string {
        return hash_hmac('sha256',$user_data, date("YmdHis"));
    }

    public function csrf_test(string $expected, string $value) : bool {
        return hash_equals($expected, $value);
    }
}
