<?php namespace App\Api;

use Response as HttpResponse;

class Response {

    public function response($data, $code, $headers = [])
    {
        return HttpResponse::json($data, $code, $headers);
    }

    public function error($message, $code = 500)
    {
        $data = [
            'status' => $code,
            'message' => $message
        ];

        return $this->response($data, $code);
    }

    public function success($data, $headers = [])
    {
        // Check if we have pagination to make...
        if (is_object($data) && get_class($data) == 'Illuminate\Pagination\LengthAwarePaginator')
        {
            $headers['Items-Total'] = $data->total();
            $headers['Pages-Total'] = $data->lastPage();
            $data = $data->items();
        }

        return $this->response($data, 200, $headers);
    }

    public function created($data, $location)
    {
        $headers['Location'] = $location;

        return $this->response($data, 201, $headers);
    }

    public function updated($location)
    {
        $headers['Location'] = $location;

        return $this->response(null, 204, $headers);
    }

    public function deleted()
    {
        return $this->response(null, 204);
    }

    public function badRequest($message = false)
    {
        if (!$message)
        {
            $message = 'Bad Request';
        }

        return $this->error($message, 400);
    }

    public function unauthorized()
    {
        return $this->error('Unauthorized', 401);
    }

    public function forbidden()
    {
        return $this->error('Forbidden', 403);
    }

    public function notFound()
    {
        return $this->error('Not Found', 404);
    }

    public function internalServerError()
    {
        return $this->error('Internal Server Error', 500);
    }

}
