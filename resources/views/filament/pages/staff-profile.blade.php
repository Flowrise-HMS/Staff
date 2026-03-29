@php
    use Modules\Staff\Enums\EmploymentStatus;
    use Modules\Staff\Enums\StaffType;
    use Modules\Staff\Enums\CredentialStatus;
    
    $statusColor = match($record->employment_status) {
        EmploymentStatus::ACTIVE => 'bg-emerald-500',
        EmploymentStatus::ON_LEAVE => 'bg-blue-500',
        EmploymentStatus::SUSPENDED => 'bg-amber-500',
        EmploymentStatus::TERMINATED => 'bg-red-500',
        default => 'bg-gray-500',
    };
    
    $typeIcon = match($record->staff_type) {
        StaffType::FULL_TIME => 'heroicon-m-briefcase',
        StaffType::PART_TIME => 'heroicon-m-clock',
        StaffType::CONTRACT => 'heroicon-m-document-text',
        StaffType::RESIDENT => 'heroicon-m-academic-cap',
        StaffType::CONSULTANT => 'heroicon-m-user-trophy',
        default => 'heroicon-m-user',
    };
@endphp

<div class="min-h-screen bg-gray-50/50 dark:bg-gray-900">
    {{-- Header Section --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                {{-- Profile Card --}}
                <div class="flex items-center gap-5">
                    <div class="relative">
                        @if($record->user?->avatar)
                            <img src="{{ $record->user->avatar }}" 
                                 alt="{{ $record->full_name }}"
                                 class="w-20 h-20 rounded-full object-cover ring-4 ring-white dark:ring-gray-700">
                        @else
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 
                                        flex items-center justify-center text-white text-2xl font-bold
                                        ring-4 ring-white dark:ring-gray-700">
                                {{ $record->initials }}
                            </div>
                        @endif
                        
                        <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full {{ $statusColor }} 
                                     ring-2 ring-white dark:ring-gray-700
                                     flex items-center justify-center">
                            @if($record->employment_status === EmploymentStatus::ACTIVE)
                                <x-heroicon-m-check class="w-4 h-4 text-white"/>
                            @elseif($record->employment_status === EmploymentStatus::TERMINATED)
                                <x-heroicon-m-x-mark class="w-4 h-4 text-white"/>
                            @else
                                <x-heroicon-m-clock class="w-4 h-4 text-white"/>
                            @endif
                        </span>
                    </div>
                    
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $record->full_name }}
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                       bg-{{ $record->staff_type->getColor() }}-100 text-{{ $record->staff_type->getColor() }}-800
                                       dark:bg-{{ $record->staff_type->getColor() }}-900/30 dark:text-{{ $record->staff_type->getColor() }}-400">
                                <x-dynamic-component :component="$typeIcon" class="w-3.5 h-3.5"/>
                                {{ $record->staff_type->getLabel() }}
                            </span>
                        </div>
                        
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-mono">{{ $record->staff_number }}</span>
                            <span class="text-gray-300 dark:text-gray-600">•</span>
                            <span class="flex items-center gap-1">
                                <x-dynamic-component :component="$statusColor === 'bg-emerald-500' ? 'heroicon-m-check-circle' : 'heroicon-m-clock'" 
                                                     class="w-4 h-4"/>
                                {{ $record->employment_status->getLabel() }}
                            </span>
                            @if($record->primary_department)
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ $record->primary_department->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Quick Stats --}}
                <div class="flex flex-wrap items-center gap-4">
                    <div class="text-center px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['tenure_years'], 1) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Years</div>
                    </div>
                    <div class="text-center px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $credentialStats['verified'] }}</div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400">Verified</div>
                    </div>
                    <div class="text-center px-4 py-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $credentialStats['expiring_soon'] }}</div>
                        <div class="text-xs text-amber-600 dark:text-amber-400">Expiring</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Personal Information --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-user class="w-5 h-5 text-gray-400"/>
                            Personal Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gender</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ $record->gender ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date of Birth</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ $record->date_of_birth?->format('M d, Y') ?? '—' }}
                                    @if($record->age)
                                        <span class="text-gray-400">({{ $record->age }} yrs)</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->phone ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->email ?? '—' }}</dd>
                            </div>
                        </dl>
                        
                        @if($record->address)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->address }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Employment Details --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-briefcase class="w-5 h-5 text-gray-400"/>
                            Employment Details
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hire Date</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->hire_date?->format('M d, Y') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenure</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($stats['tenure_years'], 1) }} years</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Staff Type</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->staff_type->getLabel() }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Account</dt>
                                <dd class="mt-1">
                                    @if($record->has_user_account)
                                        <span class="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400">
                                            <x-heroicon-m-check-circle class="w-4 h-4"/>
                                            Linked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-sm text-gray-400">
                                            <x-heroicon-m-x-circle class="w-4 h-4"/>
                                            Not Linked
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        
                        @if($record->termination_date)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="grid grid-cols-2 gap-x-6">
                                    <div>
                                        <dt class="text-sm font-medium text-red-500">Termination Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->termination_date->format('M d, Y') }}</dd>
                                    </div>
                                    @if($record->termination_reason)
                                        <div>
                                            <dt class="text-sm font-medium text-red-500">Reason</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->termination_reason }}</dd>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Departments --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-building-office class="w-5 h-5 text-gray-400"/>
                            Department Assignments
                        </h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['departments_count'] }} departments</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($record->staffDepartments as $assignment)
                            <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-4">
                                    @if($assignment->is_primary)
                                        <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $assignment->department->name }}</span>
                                            @if($assignment->is_primary)
                                                <span class="text-xs px-2 py-0.5 bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 rounded-full">Primary</span>
                                            @endif
                                        </div>
                                        @if($assignment->designation)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $assignment->designation }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $assignment->start_date?->format('M Y') ?? 'N/A' }}
                                        @if($assignment->end_date)
                                            - {{ $assignment->end_date->format('M Y') }}
                                        @else
                                            - Present
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $assignment->duration_months }} months</div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-m-building-office class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                                <p>No department assignments</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Specialties --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-star class="w-5 h-5 text-gray-400"/>
                            Specialties & Certifications
                        </h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['specialties_count'] }} specialties</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($record->specialties as $specialty)
                            <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-4">
                                    @if($specialty->is_primary)
                                        <x-heroicon-m-star class="w-5 h-5 text-amber-500"/>
                                    @else
                                        <x-heroicon-m-minus class="w-5 h-5 text-gray-300 dark:text-gray-600"/>
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $specialty->specialty_name }}</span>
                                            @if($specialty->specialty_code)
                                                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-full">{{ $specialty->specialty_code }}</span>
                                            @endif
                                            @if($specialty->is_primary)
                                                <span class="text-xs px-2 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">Primary</span>
                                            @endif
                                        </div>
                                        @if($specialty->issuing_body)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $specialty->issuing_body }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if($specialty->expiry_date)
                                        <div class="text-sm @if($specialty->is_expired) text-red-500 @elseif($specialty->is_expiring_soon) text-amber-500 @else text-gray-500 dark:text-gray-400 @endif">
                                            Expires {{ $specialty->expiry_date->format('M d, Y') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-m-star class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                                <p>No specialties recorded</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Credentials Summary --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-identification class="w-5 h-5 text-gray-400"/>
                            Credentials
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $credentialStats['verified'] }}</div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">Verified</div>
                            </div>
                            <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $credentialStats['pending'] }}</div>
                                <div class="text-xs text-amber-600 dark:text-amber-400">Pending</div>
                            </div>
                        </div>
                        
                        @if($credentialStats['expiring_soon'] > 0)
                            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                    <x-heroicon-m-exclamation-triangle class="w-5 h-5"/>
                                    <span class="text-sm font-medium">{{ $credentialStats['expiring_soon'] }} credential(s) expiring soon</span>
                                </div>
                            </div>
                        @endif
                        
                        <div class="space-y-2">
                            @forelse($record->credentials->take(5) as $credential)
                                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <div class="flex items-center gap-2">
                                        @if($credential->status === CredentialStatus::VERIFIED)
                                            <x-heroicon-m-check-circle class="w-4 h-4 text-emerald-500"/>
                                        @elseif($credential->status === CredentialStatus::EXPIRED)
                                            <x-heroicon-m-x-circle class="w-4 h-4 text-red-500"/>
                                        @else
                                            <x-heroicon-m-clock class="w-4 h-4 text-amber-500"/>
                                        @endif
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $credential->credential_type->getLabel() }}</span>
                                    </div>
                                    @if($credential->expiry_date)
                                        <span class="text-xs text-gray-400">{{ $credential->expiry_date->format('m/y') }}</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No credentials</p>
                            @endforelse
                        </div>
                        
                        @if($record->credentials->count() > 5)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-center">
                                <a href="{{ url('/admin/staff/' . $record->id . '/edit#tab-credentials') }}" 
                                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    View all {{ $record->credentials->count() }} credentials →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-m-phone class="w-5 h-5 text-gray-400"/>
                            Emergency Contact
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($record->emergency_contact_name)
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->emergency_contact_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->emergency_contact_phone ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Relationship</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->emergency_contact_relationship ?? '—' }}</dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No emergency contact recorded</p>
                        @endif
                    </div>
                </div>

                {{-- Notes --}}
                @if($record->notes)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-m-document-text class="w-5 h-5 text-gray-400"/>
                                Notes
                            </h2>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ $record->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
