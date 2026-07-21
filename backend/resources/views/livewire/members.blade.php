<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card">
        <h2 class="fh-heading mb-4">Enroll New Member</h2>

        <form wire:submit="enroll" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div>
                <label class="fh-label">Name</label>
                <input type="text" wire:model="name" class="fh-input">
                @error('name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Email</label>
                <input type="email" wire:model="email" class="fh-input">
                @error('email') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Phone</label>
                <input type="text" wire:model="phone" class="fh-input">
                @error('phone') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Password</label>
                <input type="password" wire:model="password" class="fh-input">
                @error('password') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Plan</label>
                <select wire:model.live="plan_id" class="fh-input">
                    <option value="">Select a plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} days, {{ $gym->currency_symbol }}{{ $plan->price }})</option>
                    @endforeach
                </select>
                @error('plan_id') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Start Date</label>
                <input type="date" wire:model="start_date" class="fh-input">
                @error('start_date') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Trainer (optional)</label>
                <select wire:model="trainer_id" class="fh-input">
                    <option value="">No trainer</option>
                    @foreach ($trainers as $trainer)
                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                    @endforeach
                </select>
                @error('trainer_id') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-full border-t border-chalk-2 pt-4 mt-1">
                <p class="fh-eyebrow">Initial payment <span class="normal-case tracking-normal text-steel">— optional, can also be recorded later from the member's row</span></p>
            </div>

            <div>
                <label class="fh-label">Amount Paid Now</label>
                <input type="number" step="0.01" min="0" wire:model="initial_payment_amount" placeholder="0.00" class="fh-input">
                @error('initial_payment_amount') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Payment Method</label>
                <select wire:model="initial_payment_method" class="fh-input">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="other">Other</option>
                </select>
                @error('initial_payment_method') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Note (optional)</label>
                <input type="text" wire:model="initial_payment_note" class="fh-input">
                @error('initial_payment_note') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-full flex items-end">
                <button type="submit" class="fh-btn-primary">
                    Enroll Member
                </button>
            </div>
        </form>
    </div>

    @if ($editingMemberId)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Edit Member</h2>

            <form wire:submit="updateMember" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Name</label>
                    <input type="text" wire:model="edit_name" class="fh-input">
                    @error('edit_name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Email</label>
                    <input type="email" wire:model="edit_email" class="fh-input">
                    @error('edit_email') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Phone</label>
                    <input type="text" wire:model="edit_phone" class="fh-input">
                    @error('edit_phone') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Trainer</label>
                    <select wire:model="edit_trainer_id" class="fh-input">
                        <option value="">No trainer</option>
                        @foreach ($trainers as $trainer)
                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                        @endforeach
                    </select>
                    @error('edit_trainer_id') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">Save Changes</button>
                    <button type="button" wire:click="cancelEdit" class="fh-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if ($freezingMembershipId)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Freeze Membership — {{ $freezingMemberName }}</h2>

            <form wire:submit="freezeMembership" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Expected resume date (optional)</label>
                    <input type="date" wire:model="freeze_resumes_at" class="fh-input">
                    @error('freeze_resumes_at') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">Freeze</button>
                    <button type="button" wire:click="cancelFreeze" class="fh-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if ($renewingMemberId)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Renew Membership — {{ $renewingMemberName }}</h2>

            <form wire:submit="renewMembership" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Plan</label>
                    <select wire:model="renew_plan_id" class="fh-input">
                        <option value="">Select a plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} days, {{ $plan->price }})</option>
                        @endforeach
                    </select>
                    @error('renew_plan_id') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Start Date</label>
                    <input type="date" wire:model="renew_start_date" class="fh-input">
                    @error('renew_start_date') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">Save Renewal</button>
                    <button type="button" wire:click="cancelRenewal" class="fh-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if ($lastPaymentId)
        <div class="fh-card border-turf/30">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-turf font-display uppercase text-sm tracking-wide mb-1">Payment recorded</p>
                    <p class="text-steel text-sm">Print the receipt now or find it later on the Payments page.</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('payments.receipt', $lastPaymentId) }}" target="_blank" class="fh-btn-secondary">Print Receipt</a>
                    <a href="{{ route('payments.receipt.pdf', $lastPaymentId) }}" class="fh-btn-secondary">Download PDF</a>
                    <button type="button" wire:click="dismissReceiptPrompt" class="fh-link-action text-steel-2">Dismiss</button>
                </div>
            </div>
        </div>
    @endif

    @if ($recordingPaymentFor)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Record Payment — {{ $paymentMemberName }}</h2>

            <form wire:submit="recordPayment" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Amount (balance due: {{ number_format($paymentBalanceDue, 2) }})</label>
                    <input type="number" step="0.01" wire:model="payment_amount" class="fh-input">
                    @error('payment_amount') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Method</label>
                    <select wire:model="payment_method" class="fh-input">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="other">Other</option>
                    </select>
                    @error('payment_method') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Date</label>
                    <input type="date" wire:model="payment_date" class="fh-input">
                    @error('payment_date') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Note</label>
                    <input type="text" wire:model="payment_note" class="fh-input">
                    @error('payment_note') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">Save Payment</button>
                    <button type="button" wire:click="cancelPayment" class="fh-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="fh-card-flush">
        <div class="p-4 pb-0">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or member ID (e.g. M-0007)"
                class="fh-input max-w-sm"
            >
        </div>

        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th font-mono normal-case tracking-normal">ID</th>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Email</th>
                    <th class="fh-th">Plan / Trainer</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Ends</th>
                    <th class="fh-th">Payment</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th text-right pr-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    @php $membership = $member->memberships->first(); @endphp
                    <tr>
                        <td class="fh-td-mono text-steel">{{ $member->display_code }}</td>
                        <td class="fh-td font-medium max-w-[160px] truncate" title="{{ $member->name }}">{{ $member->name }}</td>
                        <td class="fh-td text-steel max-w-[200px] truncate" title="{{ $member->email }}">{{ $member->email }}</td>
                        <td class="fh-td max-w-[150px]">
                            <div class="truncate" title="{{ $membership?->plan?->name }}">{{ $membership?->plan?->name ?? '—' }}</div>
                            <div class="truncate text-xs text-steel" title="{{ $member->trainer?->name }}">{{ $member->trainer?->name ?? 'No trainer' }}</div>
                        </td>
                        <td class="fh-td-mono">{{ $membership?->end_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="fh-td whitespace-nowrap">
                            @if ($membership?->payment_status === 'paid')
                                <span class="fh-pill-good">Paid</span>
                            @elseif ($membership?->payment_status === 'partial')
                                <span class="fh-pill-warn">Partial</span>
                            @elseif ($membership?->payment_status === 'pending')
                                <span class="fh-pill-bad">Pending</span>
                            @else
                                <span class="text-steel">—</span>
                            @endif
                            @if ($membership?->isOverdue())
                                <span class="text-tape text-xs block font-mono mt-0.5">overdue</span>
                            @endif
                        </td>
                        <td class="fh-td whitespace-nowrap">
                            @if ($membership?->isPaused())
                                <span class="fh-pill-warn">Paused</span>
                            @elseif ($membership?->isActive())
                                <span class="fh-pill-good">Active</span>
                            @else
                                <span class="fh-pill-bad">Inactive</span>
                            @endif
                        </td>
                        <td class="fh-td text-right">
                            <div
                                x-data="{
                                    open: false,
                                    menuStyle: '',
                                    toggle() {
                                        if (this.open) { this.open = false; return }
                                        const r = this.$refs.trigger.getBoundingClientRect()
                                        const w = 224
                                        const spaceBelow = window.innerHeight - r.bottom
                                        const openUp = spaceBelow < 300 && r.top > 300
                                        const left = Math.min(r.right - w, window.innerWidth - w - 8)
                                        this.menuStyle = `left:${left}px;` + (openUp ? `bottom:${window.innerHeight - r.top + 6}px;` : `top:${r.bottom + 6}px;`)
                                        this.open = true
                                    }
                                }"
                                @click.window="open && !$refs.trigger.contains($event.target) && !($refs.menu && $refs.menu.contains($event.target)) && (open = false)"
                                @scroll.window="open = false"
                                @resize.window="open = false"
                            >
                                <button
                                    x-ref="trigger"
                                    @click="toggle()"
                                    type="button"
                                    aria-haspopup="true"
                                    :aria-expanded="open"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded text-steel hover:bg-chalk hover:text-ink transition"
                                    aria-label="Actions for {{ $member->name }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                        <circle cx="12" cy="5" r="1.8" />
                                        <circle cx="12" cy="12" r="1.8" />
                                        <circle cx="12" cy="19" r="1.8" />
                                    </svg>
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-ref="menu"
                                        x-show="open"
                                        x-cloak
                                        :style="menuStyle"
                                        style="display: none;"
                                        class="fixed z-50 w-56 rounded-md border border-chalk-2 bg-white shadow-lg py-1.5"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        @keydown.escape.window="open = false"
                                    >
                                        <div class="px-3 py-2 border-b border-chalk-2 mb-1">
                                            <p class="text-sm font-medium text-ink truncate">{{ $member->name }}</p>
                                            <p class="text-xs font-mono text-steel">{{ $member->display_code }}</p>
                                        </div>

                                        <button wire:click="startRenewal({{ $member->id }})" @click="open = false" class="fh-menu-item">Renew Membership</button>
                                        @if ($membership?->isPaused())
                                            <button wire:click="resumeMembership({{ $membership->id }})" @click="open = false" class="fh-menu-item">Resume Membership</button>
                                        @elseif ($membership?->isActive())
                                            <button wire:click="startFreeze({{ $membership->id }})" @click="open = false" class="fh-menu-item">Freeze Membership</button>
                                        @endif
                                        @if ($membership && $membership->payment_status !== 'paid')
                                            <button wire:click="startPayment({{ $membership->id }})" @click="open = false" class="fh-menu-item">Record Payment</button>
                                        @endif
                                        <button wire:click="viewQr({{ $member->id }})" @click="open = false" class="fh-menu-item">View QR Code</button>
                                        <button wire:click="startEdit({{ $member->id }})" @click="open = false" class="fh-menu-item">Edit Details</button>

                                        <div class="border-t border-chalk-2 my-1"></div>

                                        <button
                                            wire:click="deleteMember({{ $member->id }})"
                                            wire:confirm="Delete {{ $member->name }}? This also removes their memberships, attendance, and progress history."
                                            @click="open = false"
                                            class="fh-menu-item text-tape hover:bg-tape/5"
                                        >Delete Member</button>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="8">No members yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @if ($viewingQrMemberId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/40"
            wire:click.self="closeQrModal"
            @keydown.escape.window="$wire.closeQrModal()"
        >
            <div class="fh-card max-w-xs w-full text-center relative">
                <button
                    wire:click="closeQrModal"
                    type="button"
                    aria-label="Close"
                    class="absolute top-3 right-3 inline-flex items-center justify-center w-8 h-8 rounded text-steel hover:bg-chalk hover:text-ink transition"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>

                <h2 class="fh-heading mb-1">{{ $viewingQrMemberName }}</h2>
                <p class="fh-eyebrow mb-4">{{ $viewingQrMemberCode }}</p>

                <img
                    src="{{ route('members.qr', $viewingQrMemberId) }}"
                    alt="QR code for {{ $viewingQrMemberName }}"
                    width="200"
                    height="200"
                    class="mx-auto border border-chalk-2 rounded"
                >

                <a
                    href="{{ route('members.qr', $viewingQrMemberId) }}"
                    download="{{ $viewingQrMemberCode }}-qr.svg"
                    class="fh-btn-secondary mt-4 inline-block"
                >Download</a>
            </div>
        </div>
    @endif
</div>
