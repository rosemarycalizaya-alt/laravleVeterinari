@props(['name','label','type'=>'text','value'=>null,'required'=>false,'options'=>[]])
<label class="field"><span>{{ $label }} @if($required)<em>*</em>@endif</span>
@if($type === 'textarea')<textarea name="{{ $name }}" rows="3" {{ $attributes }} @required($required)>{{ old($name,$value) }}</textarea>
@elseif($type === 'select')<select name="{{ $name }}" {{ $attributes }} @required($required)><option value="">Seleccionar…</option>@foreach($options as $key=>$text)<option value="{{ $key }}" @selected((string)old($name,$value)===(string)$key)>{{ $text }}</option>@endforeach</select>
@else<input name="{{ $name }}" type="{{ $type }}" @if(!in_array($type,['password','file'])) value="{{ old($name,$value) }}" @endif {{ $attributes }} @required($required)>
@endif
@error($name)<small class="field-error">{{ $message }}</small>@enderror</label>