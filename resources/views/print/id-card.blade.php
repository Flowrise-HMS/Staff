<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $staff->full_name }} – {{ __('Staff ID') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body>* {
                visibility: hidden;
            }

            .print-wrapper {
                visibility: visible;
                position: fixed;
                inset: 0;
                width: 3.375in;
                height: 2.125in;
                margin: auto;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
        }

        .barcode {
            font-family: "Libre Barcode 128", monospace;
            font-size: 38px;
            line-height: 1;
            white-space: nowrap;
        }

        .texture {
            background-image: url("data:image/svg+xml,%3Csvg width='300' height='300' viewBox='0 0 300 300' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23d9e1ff' fill-opacity='0.22'%3E%3Ccircle cx='8' cy='8' r='2'/%3E%3Ccircle cx='78' cy='8' r='2'/%3E%3Ccircle cx='148' cy='8' r='2'/%3E%3Ccircle cx='218' cy='8' r='2'/%3E%3Ccircle cx='288' cy='8' r='2'/%3E%3Ccircle cx='8' cy='78' r='2'/%3E%3Ccircle cx='78' cy='78' r='2'/%3E%3Ccircle cx='148' cy='78' r='2'/%3E%3Ccircle cx='218' cy='78' r='2'/%3E%3Ccircle cx='288' cy='78' r='2'/%3E%3Ccircle cx='8' cy='148' r='2'/%3E%3Ccircle cx='78'cy='148' r='2'/%3E%3Ccircle cx='148' cy='148' r='2'/%3E%3Ccircle cx='218' cy='148' r='2'/%3E%3Ccircle cx='288' cy='148' r='2'/%3E%3Ccircle cx='8' cy='218' r='2'/%3E%3Ccircle cx='78' cy='218' r='2'/%3E%3Ccircle cx='148' cy='218' r='2'/%3E%3Ccircle cx='218' cy='218' r='2'/%3E%3Ccircle cx='288' cy='218' r='2'/%3E%3Ccircle cx='8' cy='288' r='2'/%3E%3Ccircle cx='78' cy='288' r='2'/%3E%3Ccircle cx='148' cy='288' r='2'/%3E%3Ccircle cx='218' cy='288' r='2'/%3E%3Ccircle cx='288' cy='288' r='2'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 180px;
        }
    </style>

    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 p-10">

    @php
        $avatarUrl = $staff->user?->getFilamentAvatarUrl();
        $deptName = $primaryStaffDepartment?->department?->name;
        $branchName = $primaryStaffDepartment?->department?->branch?->name;
    @endphp

    <div class="print-wrapper flex justify-center items-center">

        <div
            class="card w-[3.375in] h-[2.125in] rounded-xl shadow-xl overflow-hidden border border-indigo-300 relative bg-white">

            <div
                class="h-6 bg-gradient-to-r from-slate-700 to-slate-500 text-white px-3 flex items-center justify-between">
                <span class="text-[9px] font-semibold tracking-wide uppercase">
                    {{ config('app.name') }}
                </span>

                <span class="text-[8px] opacity-90">{{ __('STAFF ID') }}</span>
            </div>

            <div class="texture p-2 grid grid-cols-3 gap-x-2 h-[calc(100%-1.5rem)]">

                <div class="col-span-1 flex flex-col">
                    <div class="w-[60px] h-[60px] rounded-md overflow-hidden shadow-inner bg-gray-200 flex items-center justify-center">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" class="w-[60px] h-[60px] object-cover" alt="" />
                        @else
                            <span class="text-[14px] font-bold text-gray-600">{{ $staff->initials }}</span>
                        @endif
                    </div>

                    <span class="mt-1 text-[9px] text-gray-700 font-medium">
                        {{ $staff->staff_type?->getLabel() ?? '' }}
                    </span>

                    <span class="text-[8px] text-gray-500">
                        @if($staff->hire_date)
                            {{ __('Hired') }} {{ $staff->hire_date->format('M Y') }}
                        @endif
                    </span>
                </div>

                <div class="col-span-2 flex flex-col justify-between">

                    <div>
                        <p class="text-[12px] leading-tight font-bold text-gray-900">{{ $staff->full_name }}</p>

                        <p class="text-[9px] mt-[2px] text-gray-700">
                            <span class="font-medium">{{ __('Staff #:') }}</span>
                            <span class="font-mono">{{ $staff->staff_number }}</span>
                        </p>

                        @if($deptName)
                            <p class="text-[9px] text-gray-700">
                                <span class="font-medium">{{ __('Department:') }}</span> {{ $deptName }}
                            </p>
                        @endif

                        @if($branchName)
                            <p class="text-[9px] text-gray-700">
                                <span class="font-medium">{{ __('Branch:') }}</span> {{ $branchName }}
                            </p>
                        @endif

                        @if($credential)
                            <p class="text-[8px] text-gray-600 mt-1">
                                {{ $credential->credential_type?->getLabel() ?? '' }}
                                @if($credential->credential_number)
                                    — {{ $credential->credential_number }}
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="text-[8px] text-gray-600">
                        {{ __('If found, please return to') }} {{ $branchName ?? config('app.name') }}
                    </div>
                </div>

                <div class="flex items-center mt-1 col-span-3">
                    <div class="barcode leading-none">
                        *{{ $staff->staff_number }}*
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = () => setTimeout(() => window.print(), 500);
    </script>

</body>

</html>
