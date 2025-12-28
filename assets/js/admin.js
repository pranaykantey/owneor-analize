jQuery(document).ready(function($) {
    $('#download-pdf').on('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        html2canvas(document.querySelector('#analysis-table')).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 210;
            const pageHeight = 295;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;

            let position = 0;

            doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                doc.addPage();
                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            doc.save('owneor-analysis.pdf');
        });
    });

    $('#download-image').on('click', function() {
        html2canvas(document.querySelector('#analysis-table')).then(canvas => {
            const link = document.createElement('a');
            link.download = 'owneor-analysis.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    });
});