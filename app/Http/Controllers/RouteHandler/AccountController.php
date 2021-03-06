<?php

namespace App\Http\Controllers\RouteHandler;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStore;
use App\Model\Account;
use Illuminate\Http\Request;
use Storage;

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
     * Set Request POST / GET to Global.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Class Constructor.
     *
     * @param \Illuminate\Http\Request $request User Request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->middleware('auth');
    }

    /**
     * Show list Account.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data['showDeleted'] = false;
        if ($this->request->get('show') == 'trash') {
            $data['showDeleted'] = true;
        }

        if ($data['showDeleted']) {
            $data['tableTitle'] = __('Show Trash');
            $data['accounts'] = $this->request->user()->deletedAccounts();
        } else {
            $data['tableTitle'] = __('Show Active Account');
            $data['accounts'] = $this->request->user()->accounts;
        }

        return view('account.index', $data);
    }

    /**
     * Show form for create Account.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $currencies = config('currency');

        return view('account.create', [
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store Account to Database.
     *
     * @param \App\Http\Requests\AccountStore $request Request from User after Validation
     *
     * @return \Illuminate\Http\Response
     */
    public function store(AccountStore $request)
    {
        $posted = $request->except(['_token', '_method']);
        if (!is_null($request->file('image'))) {
            $fileName = $request->user()->id.'+'.md5(time());
            $extension = $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs(
                config('account.image_path'), $fileName.'.'.$extension, 'public'
            );
            $posted['image'] = $path;
        }
        $posted['user_id'] = $request->user()->id;

        $account = Account::create($posted);

        return redirect()->route('account.index');
    }

    /**
     * Show edit form.
     *
     * @param \App\Model\Account $account Account Model
     *
     * @return \Illuminate\View\View
     */
    public function edit(Account $account)
    {
        $currencies = config('currency');
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }

        return view('account.edit', [
            'account'    => $account,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Show account transactions.
     *
     * @param \App\Model\Account $account Account Model
     *
     * @return \Illuminate\View\View
     */
    public function show(Account $account)
    {
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }
        $transactions = $account->transaction();
        $searchQuery = $this->request->get('query');
        if (!is_null($searchQuery)) {
            $transactions = $transactions->where('description', 'LIKE', '%'.$searchQuery.'%');
        }
        $transactions = $transactions->orderBy('id', 'desc');

        return view('account.show', [
            'account'      => $account,
            'transactions' => $transactions->paginate(10),
        ]);
    }

    /**
     * Store Account to Database.
     *
     * @param \App\Http\Requests\AccountStore $request Request from User after Validation
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Account $account, AccountStore $request)
    {
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }
        $posted = $request->except(['_token', '_method']);
        if (!is_null($request->file('image'))) {
            $fileName = $request->user()->id.'+'.md5(time());
            $extension = $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs(
                config('account.image_path'), $fileName.'.'.$extension, 'public'
            );
            $posted['image'] = $path;
            if (!is_null($account->image)) {
                Storage::disk('public')->delete($account->image);
            }
        }
        $update = $account->update($posted);

        return redirect()->route('account.edit', $account->id);
    }

    /**
     * Softdelete Account.
     *
     * @param \App\Model\Account $account Account Model
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }
        $account->delete();

        return redirect()->route('account.index');
    }

    /**
     * Restore Account, set deleted_at to null.
     *
     * @param int $id Account ID
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id = 0)
    {
        $account = Account::withTrashed()->findOrFail($id);
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }
        $account->restore();

        return redirect()->route('account.index');
    }

    /**
     * Permanenty delete from database.
     *
     * @param int $id Account ID
     *
     * @return \Illuminate\Http\Response
     */
    public function deletePermanent($id = 0)
    {
        $account = Account::withTrashed()->findOrFail($id);
        if ($account->user_id != $this->request->user()->id) {
            abort(403);
        }
        $account->forceDelete();

        return redirect()->route('account.index');
    }
}
