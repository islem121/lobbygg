import { startStimulusApp } from '@symfony/stimulus-bundle';
import TournamentsController from './controllers/tournaments_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('tournaments', TournamentsController);
