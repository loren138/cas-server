<?php

namespace Loren138\CASServer\Http\Controllers;

use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASLogin;
use Loren138\CASServer\Models\CASTicket;
use Loren138\CASServer\Models\PortalUsers;
use Loren138\CASServer\Models\Service;
use Illuminate\Http\Request;
use Session;

class CasController extends Controller
{
    public function __construct(Request $request)
    {
        if (config('casserver.disableNonSSL', false) && !$request->secure()) {
            throw new \Exception('Request not SSL.');
        }
    }

    public function getIndex()
    {
        return redirect('/login');
    }

    public function getLogin(
        Request $request,
        Service $service,
        CASAuthentication $CASAuthentication,
        CASTicket $CASTicket
    ) {
        if ($request->secure()) {
            $auth = $CASAuthentication->loggedIn();
            if ($auth) {
                return $this->validLogin($request, $auth, $service, $CASTicket, false);
            }
        }

        return $this->loginPage($service, $request->input('service'), '', $request->secure());
    }

    private function loginPage(
        Service $serviceModel,
        $service = '',
        $error = '',
        $secure = false
    ) {
        $serviceObject = false;
        if ($service) {
            $serviceObject = $serviceModel->validate($service);
            if ($serviceObject === false) {
                return $this->serviceError();
            }
        }
        return view('casserver::login', compact('service', 'error', 'secure', 'serviceObject'));
    }

    private function serviceError()
    {
        return view('casserver::error', [
            'errorTitle' => 'Application Not Authorized',
            'error' => 'The application you attempted to authenticate to is not authorized to use CAS.'
        ]);
    }

    private function validLogin(
        Request $request,
        CASAuthentication $auth,
        Service $service,
        CASTicket $CASTicket,
        $renew
    ) {

        if ($request->has('service')) {
            $ser = $request->input('service');
            if (!$service->validate($ser)) {
                return $this->serviceError();
            } else {
                $ticket = $CASTicket->generateTicket($auth, $ser, $renew);
                return redirect($service->redirect($ser, $ticket));
            }
        }
        return view('casserver::success', ['user' => $auth->username]);
    }

    public function postLogin(
        Request $request,
        Service $service,
        CASLogin $CASLogin,
        CASAuthentication $CASAuthentication,
        CASTicket $CASTicket
    ) {
        $user = $request->input('username');
        if ($CASLogin->validate($user, $request->ip(), $request->input('password'))) {
            $auth = $CASAuthentication->login($user, $CASLogin->userAttributes($user), $request->secure());
            return $this->validLogin($request, $auth, $service, $CASTicket, true);
        } else {
            return $this->loginPage($service, $request->input('service'), 'Invalid Login', $request->secure());
        }
    }

    /**
     * This is a CAS 1.0 Request
     *
     * @param Request $request
     * @return string
     */
    public function getValidate(Request $request, CASTicket $CASTicket)
    {
        $valid = $this->validateTicket($request, $CASTicket);

        if (is_object($valid)) {
            return "yes\n";
        }

        return "no\n";
    }

    private function validateTicket(Request $request, CASTicket $CASTicket)
    {
        $ticket = $request->input('ticket');
        $renew = filter_var($request->input('renew', false), FILTER_VALIDATE_BOOLEAN);
        try {
            return $CASTicket->validate($ticket, $request->input('service'), $renew);
        } catch (\Exception $e) {
            return 'INTERNAL_ERROR';
        }
    }

    /*
     * CAS 3.0 Validate
     */
    public function getServiceValidate3(Request $request, CASTicket $CASTicket, $attributes = true)
    {
        $service = new Service();
        $valid = $this->validateTicket($request, $CASTicket);

        if (is_object($valid)) {
            $response = [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user' => $valid->authentication->username,
                        'proxyGrantingTicket' => null,
                    ]
                ]
            ];
            if ($attributes) {
                $response['serviceResponse']['authenticationSuccess']['attributes'] = $service->attributes(
                    $request->input('service'),
                    $valid->authentication->attributeJson
                );
            }
        } else {
            $response = [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code' => $valid,
                        'description' => 'Ticket '.$request->input('ticket').' not recognized.'
                    ]
                ]
            ];
        }

        if (strtolower($request->input('format', 'XML')) === 'json') {
            return $response;
        }

        return response()->view('casserverxml::ticket_xml', $response)->header('Content-Type', 'text/xml');
    }

    /*
     * CAS 2.0 Validate
     */
    public function getServiceValidate(Request $request, CASTicket $CASTicket)
    {
        return $this->getServiceValidate3($request, $CASTicket, false);
    }

    public function getLogout(Request $request, Service $service, CASAuthentication $CASAuthentication)
    {
        $CASAuthentication->logout();
        $ser = $service->logoutRedirect($request->input('service'));
        if ($ser !== false) {
            return redirect($ser);
        }

        return view('casserver::logout');
    }
}
