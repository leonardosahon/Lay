<?php
declare(strict_types=1);
namespace Lay\libs;

/**
 * Password Encrypt Class for basic hashing
*/
abstract class LayPassword {

    public static function hash(string $password, ?string $hashed_password = null): string|bool {
        $hashed = password_hash($password,PASSWORD_DEFAULT);

        if($hashed_password)
            $hashed = password_verify($password,$hashed_password);

        return $hashed;
    }

    /**
     * Encrypts and Decrypts
     *
     * @param string|null $string value to encrypt
     * @param bool $encrypt true [default]
     * @return string|null
     */
    public static function crypt(?string $string, bool $encrypt = true): ?string {
        if($string == null) return null;
        $layer_1 = '@91_$!9u(2&y=uy+**43|\ur`y`3ut2%%iu#4#3(oo[u{3{4y7367622556';
        $layer = hash("sha512","soft-salted-prefix-bini-name-included-to-avoid-brute-force-ukpato-evboehia-okogbo" .
            $layer_1 . "soft-salted-suffix-you-should-expect-giegbefumwen-maybe-ehose-nohaso");

        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha512', $layer);
        $iv = substr( hash( 'sha512', $layer_1 ), 0, 16 );

        if($encrypt)
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        else
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);

        if(!$output) $output = null;

        return $output;
    }

    public static function csrf_gen(string $user_data, ?string $key = null) : string {
        $key = $key === null ? date("YmdHis") : $key;
        return hash_hmac('sha256',$user_data, $key);
    }
}