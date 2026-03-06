// htdocs/assets/csca-guard.js

async function isLoggedIn() {
  try {
    const res = await fetch("/api/me.php", {
      method: "GET",
      credentials: "include"
    });
    const data = await res.json();
    return data && data.ok === true;
  } catch (e) {
    return false;
  }
}

async function goToNext(nextUrl) {
  const ok = await isLoggedIn();

  if (!ok) {
    const next = encodeURIComponent(nextUrl);
    window.location.href = `/auth/login.php?next=${next}`;
    return;
  }

  window.location.href = nextUrl;
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-next]").forEach((el) => {
    el.style.cursor = "pointer";
    el.addEventListener("click", (e) => {
      e.preventDefault();
      const nextUrl = el.getAttribute("data-next");
      if (nextUrl) goToNext(nextUrl);
    });
  });
});