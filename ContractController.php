<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends ApiController
{
    public function index(Request $request)
    {
        $query = Contract::with('employee');

        if ($search = $request->query('q')) {

            $query->where('title','like',"%{$search}%")
                  ->orWhereHas('employee',function($q) use ($search){

                        $q->where('name','like',"%{$search}%");

                  });

        }

        return response()->json(

            $query->paginate(10)

        );
    }


    public function show($id)
    {

        return response()->json(

            Contract::with('employee')->findOrFail($id)

        );

    }


    public function store(Request $request)
    {

        // $this->currentUser($request);

        $validator=Validator::make($request->all(),[

            'employee_id'=>'required|exists:employees,id',

            'title'=>'required|string|max:255',

            'salary'=>'required|numeric',

            'start_date'=>'required|date',

            'end_date'=>'required|date',

            'status'=>'nullable|in:active,pending,expired'

        ]);

        if($validator->fails()){

            return response()->json([

                'errors'=>$validator->errors()

            ],422);

        }

        $contract=Contract::create(

            array_merge(

                $validator->validated(),

                [

                    'status'=>$request->input('status','active')

                ]

            )

        );

        return response()->json($contract,201);

    }



    public function update(Request $request,$id)
    {

        // $this->currentUser($request);

        $contract=Contract::findOrFail($id);

        $validator=Validator::make($request->all(),[

            'employee_id'=>'sometimes|required|exists:employees,id',

            'title'=>'sometimes|required|string|max:255',

            'salary'=>'sometimes|required|numeric',

            'start_date'=>'sometimes|required|date',

            'end_date'=>'sometimes|required|date',

            'status'=>'nullable|in:active,pending,expired'

        ]);

        if($validator->fails()){

            return response()->json([

                'errors'=>$validator->errors()

            ],422);

        }

        $contract->update(

            $validator->validated()

        );

        return response()->json(

            $contract

        );

    }



    public function destroy(Request $request,$id)
    {

        // $this->currentUser($request);

        $contract=Contract::findOrFail($id);

        $contract->delete();

        return response()->json(null,204);

    }

}