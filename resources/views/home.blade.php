@extends('layouts.app');
@section('content')

@if (strlen($large_photo_exists) > 0)
<script type="text/javascript">
function preview(img, selection) { 
	var scaleX = {{ $thumb_width }} / selection.width; 
	var scaleY = {{ $thumb_height }} / selection.height; 
	
	$('#thumbnail + div > img').css({ 
		width: Math.round(scaleX * {{ $current_large_image_width }}) + 'px', 
		height: Math.round(scaleY * {{ $current_large_image_height }}) + 'px',
		marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px', 
		marginTop: '-' + Math.round(scaleY * selection.y1) + 'px' 
	});
	$('#x1').val(selection.x1);
	$('#y1').val(selection.y1);
	$('#x2').val(selection.x2);
	$('#y2').val(selection.y2);
	$('#w').val(selection.width);
	$('#h').val(selection.height);
} 

$(document).ready(function () { 
	$('#save_thumb').click(function() {
		var x1 = $('#x1').val();
		var y1 = $('#y1').val();
		var x2 = $('#x2').val();
		var y2 = $('#y2').val();
		var w = $('#w').val();
		var h = $('#h').val();
		if(x1=="" || y1=="" || x2=="" || y2=="" || w=="" || h==""){
			alert("You must make a selection first");
			return false;
		}else{
			return true;
		}
	});
}); 

$(window).load(function () { 
	$('#thumbnail').imgAreaSelect({ aspectRatio: '1:{{ $thumb_height / $thumb_width }}', onSelectChange: preview }); 
});

</script>
@endif
<h1>Photo Upload and Crop</h1>
 @if( strlen($error) > 0 )
    <ul>
        <li><strong>Error!</strong>
        </li>{{ $error }}<li>
    </ul>
 @endif

 @if( strlen($large_photo_exists) > 0 && strlen($thumb_photo_exists) > 0 )
    
    {{!! $large_photo_exists !!}} &nbsp; {{!! $thumb_photo_exists !!}}
    <p><a href="{{ route('del') }}?t={{ session('random_key') . session('user_file_ext') }}&&id={{ $id }}">Delete images</a></p>
    <p><a href="{{ route('home', ['id' => $id]) }}">Upload another</a></p>
    @php
        session(['random_key' => '']);
        session(['user_file_ext' => '']);
    @endphp
@else
    @if( strlen($large_photo_exists) > 0 )
        <h2>Create Thumbnail</h2>
        <div align="center">
			<img src="{{ URL::to('/') }}/{{ $upload_path . $large_image_name . session('user_file_ext') }}" style="float: left; margin-right: 10px;" id="thumbnail" alt="Create Thumbnail" />
			<div style="border:1px #e5e5e5 solid; float:left; position:relative; overflow:hidden; width:{{ $thumb_width }}px; height:{{ $thumb_height }}px;">
				<img src="{{ URL::to('/') }}/{{ $upload_path . $large_image_name . session('user_file_ext') }}" style="position: relative;" alt="Thumbnail Preview" />
			</div>
			<br style="clear:both;"/>
			<form name="thumbnail" action="{{ route('thumbnail') }}" method="post">
                {{ csrf_field() }}
				<input type="hidden" name="x1" value="" id="x1" />
				<input type="hidden" name="y1" value="" id="y1" />
				<input type="hidden" name="x2" value="" id="x2" />
				<input type="hidden" name="y2" value="" id="y2" />
				<input type="hidden" name="w" value="" id="w" />
				<input type="hidden" name="h" value="" id="h" />
				<input type="submit" name="upload_thumbnail" value="Save Thumbnail" id="save_thumb" />
                <input type="hidden" name="id" value="{{ $id }}" />
			</form>
		</div>
	    <hr />
    @endif
    <h2>Upload Photo</h2>
    <form name="photo" enctype="multipart/form-data" action="{{ route('upload') }}" method="post">
        {{ csrf_field() }}
	    Photo <input type="file" name="image" size="30" /> <input type="submit" name="upload" value="Upload" />
        <input type="hidden" name="id" value="{{ $id }}" />
	</form>

@endif
@endsection