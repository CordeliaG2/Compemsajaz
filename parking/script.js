const observador = new IntersectionObserver((entradas) => {
  entradas.forEach(entrada => {
    if (entrada.isIntersecting) {
      entrada.target.classList.add("fade-in");
    }
  });
}, {
  threshold: 0.1
});

document.querySelectorAll("section").forEach(seccion => {
  seccion.classList.add("oculto");
  observador.observe(seccion);
});
