@extends('layouts.dashboard')

@section('title', 'Payment Cancelled')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card text-center">
            <div class="card-body py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10" style="width:80px;height:80px">
                        <i data-lucide="x-circle" style="width:48px;height:48px;color:#ffc107"></i>
                    </div>
                </div>

                <h3 class="mb-2">Payment Cancelled</h3>
                <p class="text-muted mb-4">
                    Your payment was not completed. No charges have been made to your account.
                </p>

                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('app.membership-plans.index') }}" class="btn btn-primary">
                        <i data-lucide="arrow-left" style="width:16px;height:16px" class="me-1"></i>
                        Back to Plans
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
