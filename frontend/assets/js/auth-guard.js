async function protectPage(requiredRole = null) {

    const res = await Iconics.check();

    if (res.status !== "success") {
        window.location.href = "auth.html";
        return null;
    }

    const user = res.data.user;

    if (requiredRole && user.role !== requiredRole) {
        window.location.href = "auth.html";
        return null;
    }

    return user;
}