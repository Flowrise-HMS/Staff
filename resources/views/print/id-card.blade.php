@extends('core::print.id-card-layout', [
    'title' => $staff->full_name.' – '.__('Staff ID'),
])

@section('content')
    @php
        $avatarUrl = $staff->user?->getFilamentAvatarUrl();
        $deptName = $primaryStaffDepartment?->department?->name;
        $branchName = $primaryStaffDepartment?->department?->branch?->name;
    @endphp

    <div class="id-card">
        @include('core::print.partials.id-card-banner', [
            'branchId' => $staff->branch_id ?? $primaryStaffDepartment?->department?->branch_id,
            'variant' => 'staff',
            'label' => __('STAFF ID'),
        ])

        <div class="id-card__body">
            <div class="id-card__photo-col">
                <div class="id-card__photo">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" />
                    @else
                        <span class="id-card__photo-initials">{{ $staff->initials }}</span>
                    @endif
                </div>

                <span class="id-card__meta">
                    {{ $staff->staff_type?->getLabel() ?? '' }}
                </span>

                <span class="id-card__meta-sub">
                    @if($staff->hire_date)
                        {{ __('Hired') }} {{ $staff->hire_date->format('M Y') }}
                    @endif
                </span>
            </div>

            <div class="id-card__details-col">
                <div>
                    <p class="id-card__name">{{ $staff->full_name }}</p>

                    <p class="id-card__line id-card__line--spaced">
                        <span class="id-card__label">{{ __('Staff #:') }}</span>
                        <span class="id-card__mono">{{ $staff->staff_number }}</span>
                    </p>

                    @if($deptName)
                        <p class="id-card__line">
                            <span class="id-card__label">{{ __('Department:') }}</span> {{ $deptName }}
                        </p>
                    @endif

                    @if($branchName)
                        <p class="id-card__line">
                            <span class="id-card__label">{{ __('Branch:') }}</span> {{ $branchName }}
                        </p>
                    @endif

                    @if($credential)
                        <p class="id-card__footnote--small" style="margin-top: 0.25rem;">
                            {{ $credential->credential_type?->getLabel() ?? '' }}
                            @if($credential->credential_number)
                                — {{ $credential->credential_number }}
                            @endif
                        </p>
                    @endif
                </div>

                <div class="id-card__footnote--small">
                    {{ __('If found, please return to') }} {{ $branchName ?? app_settings($staff->branch_id ?? $primaryStaffDepartment?->department?->branch_id)->displayName() }}
                </div>
            </div>

            <div class="id-card__barcode-row">
                <div class="barcode">*{{ $staff->staff_number }}*</div>
            </div>
        </div>
    </div>
@endsection
