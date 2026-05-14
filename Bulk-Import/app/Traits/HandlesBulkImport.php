<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HandlesBulkImport
{
    private function handleBulkFlow(string $sessionName)
    {
        $session   = session($sessionName);
        $nextIndex = ($session['index'] ?? 0) + 1;
        $total     = count($session['items'] ?? []);

        // Update index and imported count
        session([$sessionName . '.index'    => $nextIndex]);
        session([$sessionName . '.imported' => session($sessionName . '.imported', 0) + 1]);

        //After complete
        if ($nextIndex >= $total) {
            $imported = session($sessionName . '.imported');
            session()->forget($sessionName);

            return response()->json([
                'finished' => true,
                'total'    => $total,
                'imported' => $imported,
            ]);
        }
        // Next item
        return response()->json([
            'success'    => true,
            'next_index' => $nextIndex,
            'total'      => $total,
            'imported'   => session($sessionName . '.imported'),
        ]);
    }
}
