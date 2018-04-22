<?php

namespace App\Http\Controllers;

use App\Model\Account;
use App\Http\Requests\AccountStore;

/**
 * Route Handler for Account.
 *
 * @author  Azis Hapidin <azishapidin@gmail.com>
 *
 * @link    https://azishapidin.com/
 */
class AccountController extends Controller
{
    /**
     * Posted data
     * 
     * @var array
     */
    protected $posted;

    /**
     * Class Constructor
     * 
     * @return void
     * Class Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show form for create Account.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currencies = config('currency');

        return view('account.create', [
            'currencies' => $currencies
        ]);
    }

    /**
     * Store Account to Database
     * 
     * @param \App\Http\Requests\AccountStore $request Request from User after Validation
     */
    public function store(AccountStore $request)
    {
        $posted = $request->except(['_token', '_method']);
        if (!is_null($request->file('image'))) {
            $fileName = $request->user()->id . '+' . md5(time());
            $extension = $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs(
                config('account.image_path'), $fileName . '.' . $extension, 'public'
            );
            $posted['image'] = $path;
        }
        $posted['user_id'] = $request->user()->id;
        
        $account = Account::create($posted);
        return redirect()->back();
    }
}
