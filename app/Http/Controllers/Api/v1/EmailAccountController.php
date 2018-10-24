<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class EmailAccountController extends Controller
{
    public function index(Request $request, $user_id)
    {
        // Check user exists
        $user = $this->site->users()->where('users.id', $user_id)->first();
        if (!$user)
        {
            return ApiResponse::notFound();
        }

        $accounts = $user->email_accounts($this->site->id)->orderBy('from_name', 'asc')->orderBy('from_email', 'asc')->get();

        return ApiResponse::success($accounts);
    }

    public function store(Request $request, $user_id)
    {
        // Check user exists
        $user = $this->site->users()->where('users.id', $user_id)->first();
        if (!$user)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();
        $data['user_id'] = $user->id;
        $data['enabled'] = 1;

        $validator = \App\EmailAccount::getValidator($data);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Create the account
        $account = \App\EmailAccount::saveModel($data);
        if (!$account)
        {
            return ApiResponse::error('Unable to create the email account.');
        }

        // Attach to the site
        $this->site->email_accounts()->save($account);

        return ApiResponse::created(['id' => $account->id], route('api.v1.user.{user}.email_account.show', ['user' => $user->id, 'email_account' => $account->id]));
    }

    public function update(Request $request, $user_id, $id)
    {
        // Check user exists
        $user = $this->site->users()->where('users.id', $user_id)->first();
        if (!$user)
        {
            return ApiResponse::notFound();
        }

        $account = $user->email_accounts($this->site->id)->find($id);
        if (!$account)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();
        $data['user_id'] = $user->id;

        $validator = \App\EmailAccount::getUpdateValidator($data, $account->id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Update the account
        $account = \App\EmailAccount::saveModel($data, $account->id);
        if (!$account)
        {
            return ApiResponse::error('Unable to create the email account.');
        }

        return ApiResponse::updated(['id' => $account->id], route('api.v1.user.{user}.email_account.show', ['user' => $user->id, 'email_account' => $account->id]));
    }

    public function destroy(Request $request, $user_id, $id)
    {
        // Check user exists
        $user = $this->site->users()->where('users.id', $user_id)->first();
        if (!$user)
        {
            return ApiResponse::notFound();
        }

        $account = $user->email_accounts($this->site->id)->find($id);
        if (!$account)
        {
            return ApiResponse::notFound();
        }

        // Remove the account
        $account->delete();

        return ApiResponse::deleted();
    }

    public function test_user_email_account($user_id, $id)
    {
        // Check user exists
        $user = $this->site->users()->where('users.id', $user_id)->first();
        if (!$user)
        {
            return ApiResponse::notFound();
        }

        $account = $user->email_accounts($this->site->id)->find($id);
        if (!$account)
        {
            return ApiResponse::notFound();
        }

        // Test the connection
        if (!$account->test_connection())
        {
            return ApiResponse::error($account->error);
        }

        return ApiResponse::success('Connection successful.');
    }

    public function test($protocol)
    {
        $account = $this->site->email_accounts()->enabled()->where('protocol', $protocol)->first();
        if (!$account)
        {
            return ApiResponse::notFound();
        }

        // Test the connection
        if (!$account->test_connection())
        {
            return ApiResponse::error($account->error);
        }

        return ApiResponse::success('Connection successful.');
    }

}
