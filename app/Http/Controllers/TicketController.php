<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {

        try {

            DB::beginTransaction();

            $query = Ticket::query();

            $query->orderBy('created_at', 'desc');

            if ($request->search) {
                # code...
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%');
            }

            if ($request->status) {
                # code...
                $query->where('status', $request->status);
            }

            if ($request->priority) {
                # code...
                $query->where('priority', $request->priority);
            }

            if (auth()->user()->role == 'user') {
                # code...
                $query->where('user_id', auth()->user()->id);
            }

            $ticket = $query->get();


            DB::commit();

            return response([
                'message' => 'Data tiket berhasil ditampilkan',
                'data' => TicketResource::collection($ticket)
            ], 200);
        } catch (Exception $th) {
            //throw $th;
            DB::rollBack();

            return response([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function store(TicketStoreRequest $ticketStoreRequest)
    {
        $data = $ticketStoreRequest->validated();

        try {
            DB::beginTransaction();

            $ticket = new Ticket();
            $ticket->user_id = auth()->user()->id;
            $ticket->code = 'TICKET-' . rand(10000, 99999);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->save();

            DB::commit();

            return response([
                'message' => 'Berhasil embuat tiket',
                'data' => new TicketResource($ticket),
            ], 201);
        } catch (Exception $e) {
            //throw $th;
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
