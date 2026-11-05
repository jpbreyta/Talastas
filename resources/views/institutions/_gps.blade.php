<div data-gps-controls data-gps-resolve-url="{{ url('/institutions/resolve-coordinates') }}" data-gps-state="idle" class="location-method-card gps-card">
    <div class="gps-card-heading">
        <h4><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>Use Current Location (GPS)</h4>
        <span class="location-source-badge">Location source: <span data-location-source-label>Manual</span></span>
    </div>
    <button type="button" data-gps-button class="location-method-button gps-capture-button"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i><span>Use My Current Location</span></button>
    <div class="gps-help"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>
        <p>Capture while at the institution. Only locations inside mapped Lipa City boundaries are accepted. Review the pin before saving.</p>
        <p>Optional: search or enter coordinates instead. On save, device coordinates and reported accuracy are attached to the admin activity log. Read the <a href="{{ route('legal.privacy') }}" class="font-semibold text-red-900 underline">Privacy Notice</a> before requesting location. Retention and the official privacy contact are pending confirmation.</p>
    </div></div>
    @foreach(['location_source', 'actor_latitude', 'actor_longitude', 'gps_accuracy', 'gps_verified'] as $gpsField)
        @error($gpsField)<p class="text-red-600">{{ $message }}</p>@enderror
    @endforeach
    <div class="gps-status"><i data-gps-status-icon class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><p data-gps-message role="status" aria-live="polite"></p></div>
    <input type="hidden" name="location_source" value="{{ old('location_source', $institution->location_source ?? 'manual') }}">
    <input type="hidden" name="actor_latitude">
    <input type="hidden" name="actor_longitude">
    <input type="hidden" name="gps_accuracy">
    <input type="hidden" name="gps_verified" value="0">
    <label data-gps-review class="gps-review hidden"><input type="checkbox" data-gps-confirm> I checked the pin against the institution location and corrected it if needed.</label>
</div>
