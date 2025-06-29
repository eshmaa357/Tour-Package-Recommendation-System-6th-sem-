<!-- includes/signup.php -->
<!--Javascript for check email availability-->
<script>
function checkAvailability() {
    $("#loaderIcon").show();
    jQuery.ajax({
        url: "check_availability.php",
        data: 'emailid=' + $("#email").val(),
        type: "POST",
        success: function(data) {
            $("#user-availability-status").html(data);
            $("#loaderIcon").hide();
        },
        error: function () {}
    });
}
</script>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>						
            </div>
            <section>
                <div class="modal-body modal-spa">
                    <div class="login-grids">
                        <div class="login">
                            <div class="login-left">
                                <ul>
                                    <li><a class="fb" href="#"><i></i>Facebook</a></li>
                                    <li><a class="goog" href="#"><i></i>Google</a></li>
                                </ul>
                            </div>
                            <div class="login-right">
                                <!-- FORM updated: action points to signup-process.php in root -->
                                <form name="signup" method="post" action="signup-process.php">
                                    <h3>Create your account</h3>
                                    <input type="text" value="" placeholder="Full Name" name="fname" autocomplete="off" required="">
                                    <input type="text" value="" placeholder="Mobile number" maxlength="10" name="mobilenumber" autocomplete="off" required="">
                                    <input type="text" value="" placeholder="Email id" name="email" id="email" onBlur="checkAvailability()" autocomplete="off" required="">
                                    <span id="user-availability-status" style="font-size:12px;"></span>
                                    <input type="password" value="" placeholder="Password" name="password" required="">	
                                    <input type="submit" name="submit" id="submit" value="CREATE ACCOUNT">
                                </form>
                            </div>
                            <div class="clearfix"></div>								
                        </div>
                        <!-- Optional Terms and Conditions -->
                        <!-- <p>By signing up you agree to our Terms and Privacy Policy.</p> -->
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
