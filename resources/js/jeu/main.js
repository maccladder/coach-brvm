import Phaser from 'phaser';
import Maquis from './scenes/Maquis.js';

let _jeuDemarre = false;

window.demarrerJeu = function (forcer = false) {
    if (_jeuDemarre) {
        if (forcer && window.jeuRelancer) {
            window.jeuRelancer();
        } else if (window.jeuReprendre) {
            window.jeuReprendre();
        }
        return;
    }
    _jeuDemarre = true;
    new Phaser.Game({
        type: Phaser.AUTO,
        parent: 'jeu',
        width: 420,
        height: 640,
        backgroundColor: '#274d22',
        scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
        scene: [Maquis],
    });
};
