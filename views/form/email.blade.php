<div class="form-group {!! !$errors->has($errorKey) ?: 'has-error' !!}">

    <label for="{{$id}}" class="col-sm-2 control-label">{{$label}}</label>

    <div class="col-sm-6">

        @include('admin::form.error')

        <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
            <input type="email" name="{{$name}}" value="{{ old($column, $value) }}" class="form-control"  placeholder="{{ $placeholder }}" {!! $attributes !!} />
        </div>

        @include('admin::form.help-block')

    </div>
</div>
