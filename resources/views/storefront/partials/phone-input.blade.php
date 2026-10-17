@props([
    'name' => 'phone',
    'id' => null,
    'value' => '',
    'required' => true,
    'label' => null,
    'placeholder' => '01XXXXXXXXX',
    'variant' => 'default',
    'feedbackId' => null,
])

@php
    $fieldId = $id ?? $name;
    $localId = $fieldId . '_local';
    $fullValue = old($name, $value);
    $localValue = old($name . '_local', \App\Models\User::localPhoneDigits($fullValue));
    $isRequired = filter_var($required, FILTER_VALIDATE_BOOL);
    $feedbackAttr = $feedbackId ? ' data-phone-feedback="' . e($feedbackId) . '"' : '';
@endphp

@if($variant === 'checkout')
    <div class="co-input-wrap ig-phone-wrap" data-phone-wrap{!! $feedbackAttr !!}>
        <span class="co-input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 4.2 5.7 7.2 7.2l2.4-2.4c.3-.3.8-.4 1.2-.2 1.3.5 2.7.8 4.2.8.7 0 1.2.5 1.2 1.2V20c0 .7-.5 1.2-1.2 1.2C10.2 21.2 2.8 13.8 2.8 4.4 2.8 3.7 3.3 3.2 4 3.2h3.8c.7 0 1.2.5 1.2 1.2 0 1.5.3 2.9.8 4.2.1.4 0 .9-.2 1.2l-2.4 2.4z"/></svg>
        </span>
        <span class="ig-phone-prefix co-phone-prefix">+88</span>
        <input
            id="{{ $localId }}"
            class="ig-phone-local co-phone-input"
            type="tel"
            inputmode="numeric"
            maxlength="11"
            pattern="[0-9]{11}"
            placeholder="{{ $placeholder }}"
            value="{{ $localValue }}"
            @if($isRequired) required @endif
            autocomplete="tel-national"
        >
        <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
    </div>
@elseif($variant === 'register')
    <div class="reg-field ig-phone-wrap" data-phone-wrap{!! $feedbackAttr !!}>
        <label for="{{ $localId }}">@if($isRequired)<span class="req">*</span> @endif{{ $label ?? 'মোবাইল নম্বর' }}</label>
        <div class="reg-phone-input">
            <span class="reg-phone-prefix">+88</span>
            <input
                id="{{ $localId }}"
                class="reg-input ig-phone-local"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="{{ $placeholder }}"
                value="{{ $localValue }}"
                @if($isRequired) required @endif
                autocomplete="tel-national"
            >
            <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
        </div>
    </div>
@elseif($variant === 'account-field')
    <div class="account-field ig-phone-wrap" data-phone-wrap{!! $feedbackAttr !!}>
        @if($label !== null)
            <label for="{{ $localId }}">@if($isRequired)<span class="req">*</span> @endif{{ $label }}</label>
        @endif
        <div class="ig-phone-field">
            <span class="ig-phone-prefix">+88</span>
            <input
                id="{{ $localId }}"
                class="ig-phone-local"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="{{ $placeholder }}"
                value="{{ $localValue }}"
                @if($isRequired) required @endif
                autocomplete="tel-national"
            >
            <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
        </div>
    </div>
@elseif($variant === 'account-row')
    <div class="account-detail-row ig-phone-wrap" data-phone-wrap{!! $feedbackAttr !!}>
        @if($label !== null)
            <label for="{{ $localId }}">@if($isRequired)<span class="req">*</span> @endif{{ $label }}</label>
        @endif
        <div class="ig-phone-field">
            <span class="ig-phone-prefix">+88</span>
            <input
                id="{{ $localId }}"
                class="ig-phone-local"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="{{ $placeholder }}"
                value="{{ $localValue }}"
                @if($isRequired) required @endif
                autocomplete="tel-national"
            >
            <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
        </div>
    </div>
@elseif($variant === 'oc-field')
    <div class="oc-field ig-phone-wrap" data-phone-wrap{!! $feedbackAttr !!}>
        <div class="ig-phone-field">
            <span class="ig-phone-prefix">+88</span>
            <input
                id="{{ $localId }}"
                class="ig-phone-local"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="{{ $placeholder }}"
                value="{{ $localValue }}"
                @if($isRequired) required @endif
                autocomplete="tel-national"
            >
            <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
        </div>
    </div>
@else
    <div class="ig-phone-wrap mb-2" data-phone-wrap{!! $feedbackAttr !!}>
        @if($label !== null)
            <label for="{{ $localId }}" class="form-label">@if($isRequired)<span class="req">*</span> @endif{{ $label }}</label>
        @endif
        <div class="ig-phone-field">
            <span class="ig-phone-prefix">+88</span>
            <input
                id="{{ $localId }}"
                class="ig-phone-local form-control"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="[0-9]{11}"
                placeholder="{{ $placeholder }}"
                value="{{ $localValue }}"
                @if($isRequired) required @endif
                autocomplete="tel-national"
            >
            <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" class="ig-phone-hidden" value="{{ $fullValue }}">
        </div>
    </div>
@endif
