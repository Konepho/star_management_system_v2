<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        return view('rooms.index', [
            'rooms' => Room::query()
                ->orderBy('building')
                ->orderBy('floor')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('rooms.create', [
            'room' => new Room(),
            'roomTypes' => Room::typeOptions(),
            'statusOptions' => Room::statusOptions(),
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $room = Room::query()->create($request->validated());
        app(AuditLogService::class)->log('settings', 'rooms', 'created', $room, [], app(AuditLogService::class)->modelState($room), 'Created room ' . $room->name . '.');

        return redirect()
            ->route('rooms.index')
            ->with('status', 'Room created successfully.');
    }

    public function edit(Room $room): View
    {
        return view('rooms.edit', [
            'room' => $room,
            'roomTypes' => Room::typeOptions(),
            'statusOptions' => Room::statusOptions(),
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($room);
        $room->update($request->validated());
        $auditLogService->log('settings', 'rooms', 'updated', $room->fresh(), $beforeState, $auditLogService->modelState($room->fresh()), 'Updated room ' . $room->name . '.');

        return redirect()
            ->route('rooms.index')
            ->with('status', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($room);
        $wasAlreadyInactive = $room->status === Room::STATUS_INACTIVE;

        if (! $wasAlreadyInactive) {
            $room->forceFill([
                'status' => Room::STATUS_INACTIVE,
            ])->save();

            $auditLogService->log(
                'settings',
                'rooms',
                'deactivated',
                $room->fresh(),
                $beforeState,
                $auditLogService->modelState($room->fresh()),
                'Deactivated room ' . $room->name . '.',
            );
        }

        return redirect()
            ->route('rooms.index')
            ->with('status', $wasAlreadyInactive ? 'Room already inactive.' : 'Room deactivated successfully.');
    }
}
