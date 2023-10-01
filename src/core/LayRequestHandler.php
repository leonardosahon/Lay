<?php
declare(strict_types=1);
namespace Lay\core;

use Closure;
use Lay\core\enums\LayRequestMethod;

// TODO: Implement Middleware Handler
final class LayRequestHandler {
    public static function new() : self {
        return new self();
    }

    private static string $request_uri_raw;
    private static array $request_uri = [];
    private static array $request_header;
    private static array $method_arguments;
    private static mixed $method_return_value;
    private static bool $use_lay_exception = true;
    private static bool $request_found = false;
    private static bool $request_complete = false;
    private static ?string $prefix;
    private static ?string $group;
    private static string $request_method;

    private static function exception(string $title, string $message, array $stack_trace = []) : void {
        Exception::throw_exception($message, $title, true, self::$use_lay_exception, $stack_trace);
    }

    private function correct_request_method(bool $throw_exception = true) : bool {
        $match = strtoupper($_SERVER['REQUEST_METHOD']) === self::$request_method;

        if($match)
            return true;

        if($throw_exception)
            self::exception("InvalidRequestMethod", "Invalid request method received, please use a valid request verb");

        return false;
    }
    
    /**
     * Accepts `/` separated URI as arguments.
     * @param string $request_uri
     * @example `get/user/list`; translates to => `'get','user','list'`
     * @example `post/user/index/15`; translates to => `'post','user','index','{id}'`
     * @example `post/user/index/25`; translates to => `'post','user','index','{@int id}'`
     * @return $this
     */
    private function map_request(string $request_uri) : self {
        if(self::$request_found || self::$request_complete || !$this->correct_request_method(false))
            return $this;

        self::$method_arguments = [];
        $uri_text = "";
        $request_uri = trim($request_uri, "/");
        $request_uri = explode("/", $request_uri);
        $last_item = end($request_uri);

        if(isset(self::$group))
            $request_uri = [self::$group, ...$request_uri];

        if(isset(self::$prefix))
            $request_uri = [self::$prefix, ...$request_uri];

        if(count(self::$request_uri) !== count($request_uri))
            return $this;

        foreach ($request_uri as $i => $query) {
            $uri_text .= "$query, ";

            if (self::$request_uri[$i] !== $query && !str_starts_with($query, "{"))
                break;

            if(self::$request_uri[$i] === $query) {
                if($query == $last_item)
                    self::$request_found = true;

                continue;
            }

            /**
             * If request has a {placeholder}, then process it and store for future use
             */
            if(str_starts_with($query, "{")) {

                /**
                 * Strip curly braces from the placeholder for further processing. \
                 * Then get the data type if specified from the placeholder and cast it to that.
                 *
                 * Example: Using the request `users/profile/36373` \
                 * The `->map_request('users','profile','{@int 1}')` \
                 * Value will be stored as `"users.profile.has_args" => (int) 36373`
                 */
                $stripped = explode(" ", trim($query, "{}"));
                $data_type = preg_grep("/^@[a-z]+/", $stripped)[0] ?? null;

                if($data_type) {
                    $data_type = substr($data_type, 1);
                    try {
                        settype(self::$request_uri[$i], $data_type);
                    }
                    catch (\ValueError){
                        self::exception("InvalidDataType", "`@$data_type` is not a valid datatype, In [" . rtrim($uri_text, ", ") . "];");
                    }
                }

                self::$method_arguments['args'][] = self::$request_uri[$i];
                self::$request_found = true;
            }
        }

        return $this;
    }

    /**
     * @param string $prefix The prefix of the uri request. This is especially useful for multiple requests with same prefix
     * @example
     *  - /admin/profile
     *  - /admin/list
     *  - /admin/store
     *  - /admin/retire/25
     * One can represent this as:
     * LayRequestHandler::fetch()->prefix("admin")->get("profile")->get("list")->post("store")->delete("retire","{id}")
     * @return $this
     */
    public function prefix(string $prefix) : self {
        self::$prefix = $prefix;
        return $this;
    }

    public function clear_prefix() : void {
        self::$prefix = null;
    }

    /**
     * @param string $name Group name
     * @param Closure $grouped_requests a closure filled with a list of requests that depend on the group name
     * @return $this
     * @example
     * This group will serve the following routes:
     * `user/register`
     * `user/login`
     * `$req->group("user", function(LayRequestHandler $req) {
            $req->post("register")->bind(fn() => SystemUsers::new()->register())
            ->post("login")->bind(fn() => SystemUsers::new()->login());
        })`
     */
    public function group(string $name, \Closure $grouped_requests) : self {
        if(self::$request_complete)
            return $this;

        self::$group = $name;
        $grouped_requests($this);

        // Clear prefix and group when done
        self::$group = null;

        return $this;
    }

    /**
     * @see group()
     * @param Closure ...$grouped_requests A series of grouped requests that don't have group names
     * @return $this
     */
    public function groups(\Closure ...$grouped_requests) : self {
        if(self::$request_complete)
            return $this;

        foreach ($grouped_requests as $request) {
            if(self::$request_complete)
                return $this;

            $request($this);
        }

        return $this;
    }

    public function post(string $request_uri) : self {
        self::$request_method = LayRequestMethod::POST->value;
        return $this->map_request($request_uri);
    }

    public function get(string $request_uri) : self {
        self::$request_method = LayRequestMethod::GET->value;
        return $this->map_request($request_uri);
    }

    public function put(string $request_uri) : self {
        self::$request_method = LayRequestMethod::PUT->value;
        return $this->map_request($request_uri);
    }

    public function head(string $request_uri) : self {
        self::$request_method = LayRequestMethod::HEAD->value;
        return $this->map_request($request_uri);
    }

    public function delete(string $request_uri) : self {
        self::$request_method = LayRequestMethod::DELETE->value;
        return $this->map_request($request_uri);
    }

    /**
     * @param Closure $callback_of_controller_method method name of the set controller.
     * If you wish to retrieve the value of the method, ensure to return it;
     */
    public function bind(Closure $callback_of_controller_method) : self {
        if(!self::$request_found || self::$request_complete)
            return $this;

        if(!isset($_SERVER['REQUEST_METHOD']))
            self::exception("RequestMethodNotFound", "No request method found. You are probably accessing this page illegally!");

        $this->correct_request_method();

        try {
            $arguments = self::get_mapped_args();
            self::$method_return_value = $callback_of_controller_method(...$arguments);
            self::$request_complete = true;
        }
        catch (\TypeError $e){
            self::exception("MethodTypeError", $e->getMessage(), $e->getTrace());
        }
        catch (\Error $e){
            self::exception("ErrorEncountered", $e->getMessage(), $e->getTrace());
        }
        catch (\Exception $e) {
            self::exception("MethodExecutionError", $e->getMessage(), $e->getTrace());
        }

        return $this;
    }

    public function get_result() : mixed {
        // Clear the prefix, because this method marks the end of a set of api routes
        self::$prefix = null;

        try {
            return self::$method_return_value;
        } catch (\Error $e) {
            self::exception("PrematureGetResult", $e->getMessage() . "; You simply called get result and no specified route was hit, so there's nothing to 'get'");
        }

        return null;
    }

    /**
     * @param bool $print
     * @return string|bool|null Returns `null` when no api was his; Returns `false` on error; Returns json encoded string on success
     */
    public function print_as_json(bool $print = true) : string|bool|null {
        if(!isset(self::$method_return_value))
            return null;

        // Clear the prefix, because this method marks the end of a set of api routes
        self::$prefix = null;

        $x = json_encode(self::$method_return_value);

        if($print) {
            print_r($x);
            die;
        }

        return $x;
    }

    /**
     * Get the mapped out arguments of a current `->for` case
     * @return array
     */
    public function get_mapped_args() : array {
        return self::$method_arguments['args'] ?? [];
    }

    public function get_uri() : array {
        return self::$request_uri;
    }

    public function get_headers() : array {
        return self::$request_header;
    }

    /**
     * Let this class use php's Exception Class, rather than the Exception class in lay that is formatted with HTMl
     * @return self
     */
    public static function use_php_exception() : self {
        self::$use_lay_exception = false;

        return self::new();
    }

    /**
     * Capture the URI of requests sent to the api router then store it for further processing
     * @return self
     */
    public static function fetch() : self {
        if(!isset($_GET['bob_api_req'])) {
            self::exception("InvalidAPIRequest", "Invalid api request sent. Malformed URI received. You can't access this script like this!");
        }

        self::$request_found = false;
        self::$request_complete = false;
        self::$request_header = getallheaders();
        self::$request_uri_raw = $_GET['bob_api_req'];
        self::$request_uri = explode("/", rtrim($_GET['bob_api_req'],"/"));

        if(self::$request_uri[0] == "api")
            array_shift(self::$request_uri);

        if(empty(self::$request_uri[0]))
            self::exception("InvalidAPIRequest", "Invalid api request sent. Malformed URI received. You can't access this script like this!");

        return self::new();
    }

    public static function end() : ?string {
        $uri = self::$request_uri_raw ?? "";

        if(self::$request_found === false)
            self::exception("NoRequestExecuted", "No valid handler for request [$uri]. If you are sure a handler exists, then confirm if the sent [REQUEST_METHOD] matches the defined RESPONSE [REQUEST_METHOD]");

        return null;
    }
}