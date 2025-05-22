<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Display the settings form.
     */
    public function index()
    {
        $settings = [
            'hourly_rate' => config('play.hourly_rate'),
            'payment_methods' => config('play.payment_methods'),
            'lbp_exchange_rate' => config('play.lbp_exchange_rate', 90000),
        ];
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'hourly_rate' => 'required|numeric|min:0',
            'lbp_exchange_rate' => 'required|numeric|min:1',
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*' => 'required|string',
        ]);

        try {
            // Get the current config
            $configPath = config_path('play.php');
            $config = include($configPath);
            
            // Update the values - ensure proper type conversion
            $config['hourly_rate'] = (float) $validated['hourly_rate'];
            $config['lbp_exchange_rate'] = (int) $validated['lbp_exchange_rate'];
            $config['payment_methods'] = $validated['payment_methods'];
            
            // Write the updated config with proper formatting
            $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            
            // Check if file is writable
            if (!is_writable($configPath)) {
                Log::error("Config file is not writable: $configPath");
                return redirect()->route('admin.settings.index')
                    ->with('error', 'Settings file is not writable. Please check permissions.');
            }
            
            File::put($configPath, $content);
            
            // Clear config cache to make changes effective immediately
            Artisan::call('config:clear');
            
            // Log the success and new hourly rate for debugging
            Log::info("Settings updated successfully. New hourly rate: {$config['hourly_rate']}");
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully');
        } catch (\Exception $e) {
            Log::error("Error updating settings: " . $e->getMessage());
            return redirect()->route('admin.settings.index')
                ->with('error', 'An error occurred while updating settings: ' . $e->getMessage());
        }
    }
} 