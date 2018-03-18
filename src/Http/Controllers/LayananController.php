<?php

namespace Bantenprov\Layanan\Http\Controllers;

/* Require */
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Bantenprov\BudgetAbsorption\Facades\LayananFacade;

/* Models */
use Bantenprov\Layanan\Models\Bantenprov\Layanan\Layanan;
use Bantenprov\GroupEgovernment\Models\Bantenprov\GroupEgovernment\GroupEgovernment;
use App\User;

/* Etc */
use Validator;

/**
 * The LayananController class.
 *
 * @package Bantenprov\Layanan
 * @author  bantenprov <developer.bantenprov@gmail.com>
 */
class LayananController extends Controller
{  
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $group_egovernmentModel;
    protected $layanan;
    protected $user;

    public function __construct(Layanan $layanan, GroupEgovernment $group_egovernment, User $user)
    {
        $this->layanan      = $layanan;
        $this->group_egovernmentModel    = $group_egovernment;
        $this->user             = $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->has('sort')) {
            list($sortCol, $sortDir) = explode('|', request()->sort);

            $query = $this->layanan->orderBy($sortCol, $sortDir);
        } else {
            $query = $this->layanan->orderBy('id', 'asc');
        }

        if ($request->exists('filter')) {
            $query->where(function($q) use($request) {
                $value = "%{$request->filter}%";
                $q->where('label', 'like', $value)
                    ->orWhere('description', 'like', $value);
            });
        }

        $perPage = request()->has('per_page') ? (int) request()->per_page : null;
        $response = $query->paginate($perPage);

        foreach($response as $group_egovernment){
            array_set($response->data, 'group_egovernment', $group_egovernment->group_egovernment->label);
        }

        foreach($response as $user){
            array_set($response->data, 'user', $user->user->name);
        }

        return response()->json($response)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $group_egovernment = $this->group_egovernmentModel->all();
        $users = $this->user->all();

        foreach($users as $user){
            array_set($user, 'label', $user->name);
        }

        $response['group_egovernment'] = $group_egovernment;
        $response['user'] = $users;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Layanan  $layanan
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $layanan = $this->layanan;

        $validator = Validator::make($request->all(), [
            'group_egovernment_id' => 'required',
            'user_id' => 'required',
            'label' => 'required|max:16|unique:layanans,label',
            'description' => 'max:255',
        ]);

        if($validator->fails()){
            $check = $layanan->where('label',$request->label)->whereNull('deleted_at')->count();

            if ($check > 0) {
                $response['message'] = 'Failed, label ' . $request->label . ' already exists';
            } else {
                $layanan->group_egovernment_id = $request->input('group_egovernment_id');
                $layanan->user_id = $request->input('user_id');
                $layanan->label = $request->input('label');
                $layanan->description = $request->input('description');
                $layanan->save();

                $response['message'] = 'success';
            }
        } else {
            $layanan->group_egovernment_id = $request->input('group_egovernment_id');
            $layanan->user_id = $request->input('user_id');
            $layanan->label = $request->input('label');
            $layanan->description = $request->input('description');
            $layanan->save();
            $response['message'] = 'success';
        }

        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $layanan = $this->layanan->findOrFail($id);

        $response['layanan'] = $layanan;
        $response['group_egovernment'] = $layanan->group_egovernment;
        $response['user'] = $layanan->user;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Layanan  $layanan
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $layanan = $this->layanan->findOrFail($id);

        array_set($layanan->user, 'label', $layanan->user->name);

        $response['layanan'] = $layanan;
        $response['group_egovernment'] = $layanan->group_egovernment;
        $response['user'] = $layanan->user;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Layanan  $layanan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $layanan = $this->layanan->findOrFail($id);

        if ($request->input('old_label') == $request->input('label'))
        {
            $validator = Validator::make($request->all(), [
                'label' => 'required|max:16',
                'description' => 'max:255',
                'group_egovernment_id' => 'required',
                'user_id' => 'required',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'label' => 'required|max:16|unique:layanans,label',
                'description' => 'max:255',
                'group_egovernment_id' => 'required',
                'user_id' => 'required',
            ]);
        }

        if ($validator->fails()) {
            $check = $layanan->where('label',$request->label)->whereNull('deleted_at')->count();

            if ($check > 0) {
                $response['message'] = 'Failed, label ' . $request->label . ' already exists';
            } else {
                $layanan->label = $request->input('label');
                $layanan->description = $request->input('description');
                $layanan->group_egovernment_id = $request->input('group_egovernment_id');
                $layanan->user_id = $request->input('user_id');
                $layanan->save();

                $response['message'] = 'success';
            }
        } else {
            $layanan->label = $request->input('label');
            $layanan->description = $request->input('description');
            $layanan->group_egovernment_id = $request->input('group_egovernment_id');
            $layanan->user_id = $request->input('user_id');
            $layanan->save();

            $response['message'] = 'success';
        }

        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Layanan  $layanan
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $layanan = $this->layanan->findOrFail($id);

        if ($layanan->delete()) {
            $response['status'] = true;
        } else {
            $response['status'] = false;
        }

        return json_encode($response);
    }
}