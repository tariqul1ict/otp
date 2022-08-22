<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
class LoginController extends Controller
{
    

    use AuthenticatesUsers;

    function otp(Request $request){


        request()->validate([
            'email'=>'required|email|exists:users',
            'password'=>'required|min:8'
        ]);
        
        $user = User::where('email', request('email'))->first();

        if (Hash::check(request('password'), $user->password) ===false) {

            $alert = [
                'type'=>'error',
                'message' =>'Username or Password incorrect'
            ];

           return redirect()->back()->with($alert)->withInput();
        };

        $credentials = [
            'email' =>request()->email,
            'password'=>request()->password,
            'remember'=>request()->remember,
        ];
        $otpCode  = rand(111111,999999);

        session()->put('otp', $otpCode );
        session()->put('loginData', $credentials); 



        $url = "http://66.45.237.70/api.php";
        $number="88017,88018,88019";
        $text="Your Test Code is: ".$otpCode .'. This message only for api test purpose';
        $data= array(
        'username'=>"01736234377",
        'password'=>"57066584",
        'number'=>$user->contact,
        'message'=>"$text"
        );

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsresult = curl_exec($ch);
        $p = explode("|",$smsresult);
        $sendstatus = $p[0];

        return redirect()->route('opt-get');
    }

    function otpget(Request $request){  
        return view('auth.otp');
    }



    public function login(Request $request)
    {

        $old = (string) session()->get('otp');
        $new =$request->otp;

      

        if ($old  !== $new) {
            $alert = [
                'type'=>'error',
                'message' =>'Verification Failed'
            ];

           return redirect()->route('opt-get')->with($alert)->withInput();
        }



        $loginData = session()->get('loginData');

     
        $remember = @$loginData['remember'];
        $email = $loginData['email'];
        $password = $loginData['password'];

        if ($request->get('remember') == '') {
            $remember = false;
        } else {
            $remember = true;
        }

        $credentials =[
            'email'=>$email,
            'password'=> $password
        ];
        
        if (Auth::attempt($credentials, $remember)) {

        session()->forget('otp');
        session()->forget('loginData');

            return redirect()->intended('home');
        }
        return redirect("login")->withInput()->with('message', 'You have entered invalid credentials');
    }



    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
