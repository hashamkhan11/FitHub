<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Members extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:members,email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|exists:plans,id')]
    public ?int $plan_id = null;

    #[Validate('nullable|exists:users,id')]
    public ?int $trainer_id = null;

    #[Validate('required|date')]
    public string $start_date = '';

    #[Validate('nullable|numeric|min:0')]
    public string $initial_payment_amount = '';

    #[Validate('required|in:cash,card,bank_transfer,other')]
    public string $initial_payment_method = 'cash';

    #[Validate('nullable|string|max:255')]
    public string $initial_payment_note = '';

    public ?int $viewingQrMemberId = null;

    public string $viewingQrMemberName = '';

    public string $viewingQrMemberCode = '';

    public ?int $recordingPaymentFor = null;

    public ?int $lastPaymentId = null;

    public string $paymentMemberName = '';

    public float $paymentBalanceDue = 0;

    public string $payment_amount = '';

    public string $payment_date = '';

    public string $payment_method = 'cash';

    public string $payment_note = '';

    public string $search = '';

    public ?int $editingMemberId = null;

    public string $edit_name = '';

    public string $edit_email = '';

    public string $edit_phone = '';

    public ?int $edit_trainer_id = null;

    public ?int $renewingMemberId = null;

    public string $renewingMemberName = '';

    public ?int $renew_plan_id = null;

    public string $renew_start_date = '';

    public ?int $freezingMembershipId = null;

    public string $freezingMemberName = '';

    public string $freeze_resumes_at = '';

    public function mount(): void
    {
        Gate::authorize('view-members');

        $this->start_date = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.members', [
            'members' => Member::where('gym_id', auth()->user()->gym_id)
                ->when($this->search !== '', function ($query) {
                    $term = $this->search;
                    $query->where(function ($query) use ($term) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('member_code', 'like', "%{$term}%");
                    });
                })
                ->with(['memberships' => fn ($q) => $q->latest('end_date')->with('plan'), 'trainer'])
                ->latest()
                ->get(),
            'plans' => Plan::where('gym_id', auth()->user()->gym_id)->where('is_active', true)->get(),
            'trainers' => User::where('gym_id', auth()->user()->gym_id)->where('role', 'trainer')->get(),
            'gym' => auth()->user()->gym,
        ]);
    }

    public function updatedPlanId($value): void
    {
        if ($this->initial_payment_amount !== '' || ! $value) {
            return;
        }

        $plan = Plan::where('gym_id', auth()->user()->gym_id)->find($value);

        if ($plan) {
            $this->initial_payment_amount = number_format((float) $plan->price, 2, '.', '');
        }
    }

    public function enroll(): void
    {
        Gate::authorize('manage-members');

        $this->validate();

        $plan = Plan::where('gym_id', auth()->user()->gym_id)->findOrFail($this->plan_id);
        $trainerId = $this->trainer_id ? $this->findTrainer($this->trainer_id)->id : null;
        $paymentAmount = (float) ($this->initial_payment_amount ?: 0);

        if ($paymentAmount > (float) $plan->price + 0.01) {
            $this->addError('initial_payment_amount', 'Amount exceeds the plan price of '.number_format($plan->price, 2).'.');

            return;
        }

        DB::transaction(function () use ($plan, $trainerId, $paymentAmount) {
            $member = Member::create([
                'gym_id' => auth()->user()->gym_id,
                'trainer_id' => $trainerId,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'join_date' => now(),
            ]);

            $membership = $member->memberships()->create([
                'plan_id' => $plan->id,
                'start_date' => $this->start_date,
                'end_date' => Carbon::parse($this->start_date)->addDays($plan->duration_days),
                'payment_status' => 'pending',
                'price_paid' => $plan->price,
            ]);

            if ($paymentAmount > 0) {
                $membership->payments()->create([
                    'gym_id' => auth()->user()->gym_id,
                    'amount' => $paymentAmount,
                    'method' => $this->initial_payment_method,
                    'paid_at' => now(),
                    'note' => $this->initial_payment_note ?: null,
                ]);

                $membership->syncPaymentStatus();
            }
        });

        ActivityLog::record('member.enrolled', "Enrolled {$this->name} on the {$plan->name} plan.");

        $this->reset(['name', 'email', 'phone', 'password', 'plan_id', 'trainer_id', 'initial_payment_amount', 'initial_payment_note']);
        $this->start_date = now()->toDateString();
    }

    public function viewQr(int $memberId): void
    {
        $member = $this->findMember($memberId);

        $this->viewingQrMemberId = $member->id;
        $this->viewingQrMemberName = $member->name;
        $this->viewingQrMemberCode = $member->display_code;
    }

    public function closeQrModal(): void
    {
        $this->reset(['viewingQrMemberId', 'viewingQrMemberName', 'viewingQrMemberCode']);
    }

    public function startRenewal(int $memberId): void
    {
        $this->cancelEdit();
        $this->cancelPayment();
        $this->cancelFreeze();

        $member = $this->findMember($memberId);
        $latestMembership = $member->memberships()->latest('end_date')->first();

        $this->renewingMemberId = $member->id;
        $this->renewingMemberName = $member->name;
        $this->renew_plan_id = null;
        $this->renew_start_date = ($latestMembership && ! $latestMembership->end_date->isPast())
            ? $latestMembership->end_date->addDay()->toDateString()
            : now()->toDateString();
    }

    public function renewMembership(): void
    {
        Gate::authorize('manage-members');

        $this->validate([
            'renew_plan_id' => 'required|exists:plans,id',
            'renew_start_date' => 'required|date',
        ]);

        $member = $this->findMember($this->renewingMemberId);
        $plan = Plan::where('gym_id', auth()->user()->gym_id)->findOrFail($this->renew_plan_id);

        $member->memberships()->create([
            'plan_id' => $plan->id,
            'start_date' => $this->renew_start_date,
            'end_date' => Carbon::parse($this->renew_start_date)->addDays($plan->duration_days),
            'payment_status' => 'pending',
            'price_paid' => $plan->price,
        ]);

        ActivityLog::record('membership.renewed', "Renewed membership for {$member->name} on the {$plan->name} plan.");

        $this->cancelRenewal();
    }

    public function cancelRenewal(): void
    {
        $this->reset(['renewingMemberId', 'renewingMemberName', 'renew_plan_id', 'renew_start_date']);
    }

    public function startFreeze(int $membershipId): void
    {
        $this->cancelEdit();
        $this->cancelRenewal();
        $this->cancelPayment();

        $membership = $this->findMembership($membershipId);

        $this->freezingMembershipId = $membership->id;
        $this->freezingMemberName = $membership->member->name;
        $this->freeze_resumes_at = '';
    }

    public function freezeMembership(): void
    {
        Gate::authorize('manage-members');

        $this->validate([
            'freeze_resumes_at' => 'nullable|date|after:today',
        ]);

        $membership = $this->findMembership($this->freezingMembershipId);
        $membership->freeze($this->freeze_resumes_at ?: null);

        ActivityLog::record('membership.frozen', "Froze membership for {$this->freezingMemberName}.");

        $this->cancelFreeze();
    }

    public function cancelFreeze(): void
    {
        $this->reset(['freezingMembershipId', 'freezingMemberName', 'freeze_resumes_at']);
    }

    public function resumeMembership(int $membershipId): void
    {
        Gate::authorize('manage-members');

        $membership = $this->findMembership($membershipId);
        $membership->resume();

        ActivityLog::record('membership.resumed', "Resumed membership for {$membership->member->name}.");
    }

    public function startPayment(int $membershipId): void
    {
        $this->cancelEdit();
        $this->cancelRenewal();
        $this->cancelFreeze();

        $membership = $this->findMembership($membershipId);

        $this->recordingPaymentFor = $membership->id;
        $this->paymentMemberName = $membership->member->name;
        $this->paymentBalanceDue = $membership->balance_due;
        $this->payment_amount = number_format($membership->balance_due, 2, '.', '');
        $this->payment_date = now()->toDateString();
        $this->payment_method = 'cash';
        $this->payment_note = '';
    }

    public function recordPayment(): void
    {
        Gate::authorize('manage-payments');

        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer,other',
            'payment_note' => 'nullable|string|max:255',
        ]);

        try {
            $payment = DB::transaction(function () {
                $membership = Membership::whereHas('member', fn ($q) => $q->where('gym_id', auth()->user()->gym_id))
                    ->lockForUpdate()
                    ->findOrFail($this->recordingPaymentFor);

                if ((float) $this->payment_amount > $membership->balance_due + 0.01) {
                    throw new \DomainException('Amount exceeds the remaining balance of '.number_format($membership->balance_due, 2).'.');
                }

                $payment = $membership->payments()->create([
                    'gym_id' => auth()->user()->gym_id,
                    'amount' => $this->payment_amount,
                    'method' => $this->payment_method,
                    'paid_at' => $this->payment_date,
                    'note' => $this->payment_note ?: null,
                ]);

                $membership->syncPaymentStatus();

                return $payment;
            });
        } catch (\DomainException $e) {
            $this->addError('payment_amount', $e->getMessage());

            return;
        }

        ActivityLog::record('payment.recorded', "Recorded {$this->payment_method} payment of ".number_format($payment->amount, 2)." for {$this->paymentMemberName}.");

        $this->lastPaymentId = $payment->id;

        $this->cancelPayment();
    }

    public function cancelPayment(): void
    {
        $this->reset(['recordingPaymentFor', 'paymentMemberName', 'paymentBalanceDue', 'payment_amount', 'payment_date', 'payment_method', 'payment_note']);
    }

    public function dismissReceiptPrompt(): void
    {
        $this->lastPaymentId = null;
    }

    private function findMembership(?int $membershipId): Membership
    {
        return Membership::whereHas('member', fn ($q) => $q->where('gym_id', auth()->user()->gym_id))
            ->findOrFail($membershipId);
    }

    public function startEdit(int $memberId): void
    {
        $this->cancelPayment();
        $this->cancelRenewal();
        $this->cancelFreeze();

        $member = $this->findMember($memberId);

        $this->editingMemberId = $member->id;
        $this->edit_name = $member->name;
        $this->edit_email = $member->email;
        $this->edit_phone = $member->phone ?? '';
        $this->edit_trainer_id = $member->trainer_id;
    }

    public function updateMember(): void
    {
        Gate::authorize('manage-members');

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => ['required', 'email', Rule::unique('members', 'email')->ignore($this->editingMemberId)],
            'edit_phone' => 'nullable|string|max:20',
            'edit_trainer_id' => 'nullable|exists:users,id',
        ]);

        $trainerId = $this->edit_trainer_id ? $this->findTrainer($this->edit_trainer_id)->id : null;

        $this->findMember($this->editingMemberId)->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
            'phone' => $this->edit_phone ?: null,
            'trainer_id' => $trainerId,
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingMemberId', 'edit_name', 'edit_email', 'edit_phone', 'edit_trainer_id']);
        $this->resetErrorBag(['edit_name', 'edit_email', 'edit_phone', 'edit_trainer_id']);
    }

    public function deleteMember(int $memberId): void
    {
        Gate::authorize('manage-members');

        $member = $this->findMember($memberId);
        $name = $member->name;
        $code = $member->display_code;
        $member->update(['email' => null]);
        $member->delete();

        ActivityLog::record('member.deleted', "Deleted member {$name} ({$code}).");
    }

    private function findMember(?int $memberId): Member
    {
        return Member::where('gym_id', auth()->user()->gym_id)->findOrFail($memberId);
    }

    private function findTrainer(int $trainerId): User
    {
        return User::where('gym_id', auth()->user()->gym_id)->where('role', 'trainer')->findOrFail($trainerId);
    }
}
