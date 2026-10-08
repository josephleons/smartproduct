<div class="mb-4">
  <label class="form-label" for="amount">{{ __('Amount (TZS)') }}</label>
  <input type="text" inputmode="numeric" id="amount" name="amount" value="{{ old('amount', $amount) }}" class="form-control @error('amount') is-invalid @enderror" required>
  @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-4">
  <label class="form-label" for="method">{{ __('Paid with') }}</label>
  <select id="method" name="method" class="form-select @error('method') is-invalid @enderror">
    @foreach (App\Enums\PaymentMethod::cases() as $method)
      <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="form-label" for="reference">{{ __('Transaction / receipt number') }}</label>
  <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror" placeholder="QJK3X8Y2LM" required>
  @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
