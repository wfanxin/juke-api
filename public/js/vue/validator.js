function num_word (rule, value, callback) {
    // eslint-disable-next-line space-unary-ops
    if (! rule.required && value === '') {
        return callback()
    }

    if (! value.match(/^[A-Za-z0-9]+$/)) {
        return callback(new Error('只能输入英文字母和数字'))
    }

    return callback()
}

function username (rule, value, callback) {
    if (value.length < 8 || value.length > 15) {
        return callback(new Error('只能输入8-15位英文字母和数字'))
    }

    if (! value.match(/^[A-Za-z0-9]+$/)) {
        return callback(new Error('只能输入8-15位英文字母和数字'))
    }

    // return callback()
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("/username",{'user_name': value},function(e){
        if (e.code == 10008) {
            return callback(new Error(e.message))
        } else if (e.code != 0) {
            alert(e.message);
        } else if (e.code == 0) {
            return callback();
        }
    });
}

function checkPwd (rule, value, callback) {
    // eslint-disable-next-line space-unary-ops
    if (! rule.required && value === '') {
        return callback()
    }

    if (value.length <= 8) {
        return callback(new Error('密码需要大于8位'))
        // eslint-disable-next-line space-unary-ops
    } else if (! value.match(/^[A-Za-z0-9]+$/)) {
        return callback(new Error('密码只能英文字母和数字'))
    }

    return callback()
}