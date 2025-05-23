<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
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

    public function show($code)
    {
        try {
            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                return response([
                    'message' => 'Tiket tidak ditemukan',
                    'data' => null,
                ], 404);
            }

            if (auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id) {
                # code...
                return response([
                    'message' => 'Anda tidak berhak mengakses code tiket tersebut',
                    'data' => null
                ], 403);
            }

            return response([
                'message' => 'Data tiket berhasil ditampilkan',
                'data' => new TicketResource($ticket)
            ], 200);
        } catch (Exception $th) {
            //throw $th;
            return response([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function replyTicket(TicketReplyRequest $ticketReplyRequest, $code)
    {
        $data = $ticketReplyRequest->validated();

        try {
            DB::beginTransaction();

            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                # code...
                return response([
                    'message' => 'Tiket tidak ditemukan',
                    'data' => null,
                ], 404);
            }

            if (auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id) {
                # code...
                return response([
                    'message' => 'Anda tidak berhak mengakses code tiket tersebut',
                    'data' => null
                ], 403);
            }

            $ticketReply = new TicketReply();
            $ticketReply->ticket_id = $ticket->id;
            $ticketReply->user_id = auth()->user()->id;
            $ticketReply->content = $data['content'];
            $ticketReply->save();

            if (auth()->user()->role == 'admin') {
                # code...
                $ticket->status = $data['status'];
                if ($data['status'] == 'resolved') {
                    $ticket->completed_at = now();
                }
                $ticket->save();
            }

            DB::commit();

            return response([
                'message' => 'Berhasil mengirim balasan',
                'data' => new TicketReplyResource($ticketReply)
            ], 201);
        } catch (\Exception $th) {
            //throw $th;
            return response([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
