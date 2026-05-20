(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.dpTogglePanel = {
    attach: function (context, settings) {
      const toggleBtns = once('dp-toggle-behavior', '#dp-toggle-btn', context);

      toggleBtns.forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function (e) {
          e.preventDefault();
          
          const detailsPanel = document.getElementById('dp-details-panel');

          if (detailsPanel) {
            detailsPanel.classList.toggle('open');
            toggleBtn.classList.toggle('active');
          }
        });
      });
    }
  };

  Drupal.behaviors.dpPasswordActions = {
    attach: function (context, settings) {
      const passwordElements = once('dp-password-behavior', '.dp-password-val', context);

      passwordElements.forEach(container => {
        const seenBtn = container.querySelector('.dp-action-seen');
        const copyBtn = container.querySelector('.dp-action-copy');
        const actualValue = container.getAttribute('data-value');

        if (!actualValue) return;

        const maskedValue = '●●●●●●●';

        let textNode = null;
        for (let node of container.childNodes) {
          if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim().length > 0) {
            textNode = node;
            break;
          }
        }

        if (seenBtn && textNode) {
          seenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isVisible = container.classList.contains('is-visible');
            
            if (isVisible) {
              textNode.nodeValue = ' ' + maskedValue + ' ';
              container.classList.remove('is-visible');
              seenBtn.classList.remove('active');
            } else {
              textNode.nodeValue = ' ' + actualValue + ' ';
              container.classList.add('is-visible');
              seenBtn.classList.add('active');
            }
          });
        }

        if (copyBtn) {
          copyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(actualValue).then(() => {
                copyBtn.classList.add('copied');
                
                let noticeMsg = container.querySelector('.dp-copy-notice');
                
                if (!noticeMsg) {
                  noticeMsg = document.createElement('span');
                  noticeMsg.className = 'dp-copy-notice';
                  noticeMsg.textContent = ' Copied!';
                  
                  noticeMsg.style.fontSize = '12px';
                  noticeMsg.style.color = '#10b981';
                  noticeMsg.style.fontWeight = 'bold';
                  noticeMsg.style.marginLeft = '8px';
                  noticeMsg.style.animation = 'fadeIn 0.2s ease-in-out';

                  copyBtn.parentNode.insertBefore(noticeMsg, copyBtn.nextSibling);

                  setTimeout(() => {
                    if (noticeMsg && noticeMsg.parentNode) {
                      noticeMsg.parentNode.removeChild(noticeMsg);
                    }
                    copyBtn.classList.remove('copied');
                  }, 2000);
                }
              }).catch(err => {
                console.error('Error when copy password: ', err);
              });
            }
          });
        }
      });
    }
  };

})(Drupal, once);