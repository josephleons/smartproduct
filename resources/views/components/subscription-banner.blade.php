{{-- Shown at the top of every company dashboard page when billing needs attention. --}}
@php
  use App\Enums\SubscriptionStatus;
  $user = auth()->user();
  $subscription = $user?->company?->subscription;
  $daysLeft = $subscription ? (int) now()->startOfDay()->diffInDays($subscription->paid_until, false) : null;
  $canPay = $user?->isOwner();
@endphp

@if ($subscription && !$user->isSuperAdmin())
  @if ($subscription->status === SubscriptionStatus::ReadOnly)
    <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>{{ __('Your subscription is unpaid, so your account is read-only. Customers can still scan your labels.') }}</span>
      @if ($canPay)<a href="{{ route('billing.show') }}" class="btn btn-sm btn-danger">{{ __('Pay now') }}</a>@endif
    </div>
  @elseif ($subscription->status === SubscriptionStatus::PastDue)
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>{{ __('Your subscription payment is overdue. Pay before :date to keep full access.', ['date' => $subscription->grace_ends_at?->format('d M Y')]) }}</span>
      @if ($canPay)<a href="{{ route('billing.show') }}" class="btn btn-sm btn-warning">{{ __('Pay now') }}</a>@endif
    </div>
  @elseif ($subscription->status === SubscriptionStatus::Trial && $daysLeft !== null && $daysLeft <= 3)
    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>{{ trans_choice('Your free trial ends in :count day.|Your free trial ends in :count days.', max(0, $daysLeft)) }}</span>
      @if ($canPay)<a href="{{ route('billing.show') }}" class="btn btn-sm btn-info">{{ __('See billing') }}</a>@endif
    </div>
  @endif
@endif
