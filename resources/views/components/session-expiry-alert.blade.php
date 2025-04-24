@props(['session', 'minutesRemaining'])

<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-yellow-700">
                <span class="font-medium">Session Alert:</span>
                <span class="font-bold">{{ $session->child->name }}'s</span> session has been running for 
                @if($session->planned_hours)
                    <span class="font-medium">{{ $minutesRemaining }} minutes</span> remaining of planned {{ $session->planned_hours }} hour(s).
                @else
                    <span class="font-medium">{{ floor($minutesRemaining / 60) }}h {{ $minutesRemaining % 60 }}m</span>.
                @endif
            </p>
            <div class="mt-2">
                <a href="{{ route('cashier.sessions.show-end', $session->id) }}" 
                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    End Session
                </a>
            </div>
        </div>
    </div>
</div> 