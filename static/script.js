// criar uma função para quando clicar em um card service-card anexar o titulo do card pra iniciar uma conversa pelo whatsapp
document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('click', function() {
            const serviceName = card.querySelector('p').textContent;
            const whatsappLink = `https://api.whatsapp.com/send?phone=5582996733252&text=Olá!%20Gostaria%20de%20fazer%20um%20pedido%20de%20${serviceName}`;
            window.open(whatsappLink, '_blank');
        });
    });
});

//criar uma função para compartilhar o site atual nas redes sociais <a href="#" class="social-icon" title="Compartilhar">
document.addEventListener('DOMContentLoaded', function() {
    const shareIcon = document.querySelector('.social-icon[title="Compartilhar"]');
    shareIcon.addEventListener('click', function() {
        const shareData = {
            title: 'Agulha e Linha',
            text: 'Confira o site Agulha e Linha!',
            url: window.location.href
        };
        navigator.share(shareData);
    });
});

