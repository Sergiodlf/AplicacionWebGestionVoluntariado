import { Injectable } from '@angular/core';

@Injectable({
    providedIn: 'root'
})
export class LoadingService {
    private _isFirstLoad = true;

    get isFirstLoad(): boolean {
        return this._isFirstLoad;
    }

    setFirstLoadComplete() {
        this._isFirstLoad = false;
    }
}
